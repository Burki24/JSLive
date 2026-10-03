// Real gauge setters and scheduler, deterministic clock; canvas is the only double.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const template = fs.readFileSync(path.join(root, 'SymconJSLive/templates/CanvasGauges-Radial.html'), 'utf8');
const asset = template.match(/src="\/hook\/JSLive\/(js\/canvas-gauges\/[^"\s]+)"/)[1];
const bundle = fs.readFileSync(process.env.JSLIVE_GAUGE_BASELINE || path.join(root, 'SymconJSLive', asset), 'utf8');
function harness(radial, options = {}) {
    let time = 0, next = 0;
    const frames = new Map();
    const sandbox = { console, setTimeout, clearTimeout, module: { exports: {} } };
    sandbox.window = sandbox;
    sandbox.GAUGES_NO_AUTO_INIT = true;
    sandbox.performance = { now: () => time };
    sandbox.requestAnimationFrame = callback => { frames.set(++next, callback); return next; };
    sandbox.cancelAnimationFrame = id => frames.delete(id);
    // Exported prototypes allow exercising real setters without canvas setup.
    vm.runInNewContext(bundle, sandbox);
    const library = sandbox.module.exports;
    const Type = radial ? library.RadialGauge : library.LinearGauge;
    const gauge = Object.create(Type.prototype);
    const events = [];
    gauge.options = { value: 0, minValue: 0, maxValue: 360, animation: true,
        animatedValue: false, ticksAngle: 360, useMinPath: false, ...options };
    gauge.animation = new library.Animation('linear', 100);
    gauge.emit = (event, ...args) => events.push([event, ...args]);
    gauge.draw = () => { gauge.rendered = gauge.options.value; return gauge; };
    function step(ms) {
        time += ms;
        const current = Array.from(frames.entries());
        frames.clear();
        current.forEach(([, callback]) => callback(time));
    }
    return { gauge, events, step, frames };
}
let count = 0;
for (const radial of [false, true]) {
    for (const progress of [0, 25, 99]) {
        for (const values of [[180, 360], [180, 60], [180, 60, 300], [180, 0]]) {
            const h = harness(radial);
            h.gauge.value = values[0];
            h.step(progress);
            values.slice(1).forEach(value => { h.gauge.value = value; });
            const target = values[values.length - 1];
            assert.equal(h.gauge.value, target, 'Getter must expose the newest requested target.');
            h.step(150);
            assert.equal(h.gauge.value, target, 'Completion must not restore an obsolete target.');
            assert.equal(h.gauge.options.value, target);
            assert.equal(h.gauge.rendered, target);
            assert.equal(h.frames.size, 0, 'No pending frames after completion.');
            count++;
        }
    }
    // A repeated target must not jump to the endpoint or cancel its animation.
    {
        const h = harness(radial);
        h.gauge.value = 180; h.step(25);
        const before = h.gauge.options.value;
        h.gauge.value = 180;
        assert.equal(h.gauge.options.value, before);
        h.step(100);
        assert.equal(h.gauge.rendered, 180);
        assert.equal(h.events.filter(e => e[0] === 'animationEnd').length, 1);
        count++;
    }
    // Selecting the currently rendered intermediate value cancels the old target.
    {
        const h = harness(radial);
        h.gauge.value = 180; h.step(50);
        h.gauge.value = 90; h.step(150);
        assert.equal(h.gauge.value, 90); assert.equal(h.gauge.rendered, 90);
        count++;
    }
    // Switching animation off while it runs must not restore the old value later.
    {
        const h = harness(radial);
        h.gauge.value = 180; h.step(25);
        h.gauge.options.animation = false;
        h.gauge.value = 60; h.step(150);
        assert.equal(h.gauge.value, 60); assert.equal(h.gauge.rendered, 60);
        count++;
    }
    // animateOnInit seeds _value before the first setter call.
    {
        const h = harness(radial);
        h.gauge._value = 180;
        h.gauge.value = 180; h.step(150);
        assert.equal(h.gauge.rendered, 180);
        count++;
    }
}
for (const [from, first, latest, middle] of [[350, 10, 30, 375], [10, 350, 330, -15]]) {
    const h = harness(true, { value: from, useMinPath: true });
    h.gauge.value = first; h.step(50);
    h.gauge.value = latest; h.step(50);
    assert.equal(h.gauge.value, latest, 'Shortest-path getter retains the original target.');
    assert.equal(h.gauge.options.value, middle, 'Keep the short rotation across north.');
    h.step(100);
    assert.equal(h.gauge.value, latest); assert.equal(h.gauge.rendered, latest);
    count++;
}
console.log(`Gauge animation interruption verified (${count} deterministic cases).`);
