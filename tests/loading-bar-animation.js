const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const template = fs.readFileSync(path.join(root, 'SymconJSLive/templates/Progressbar.html'), 'utf8');
const asset = template.match(/src="\/hook\/JSLive\/(js\/loading-Bar\/[^"\s]+)"/)[1];
const bundle = fs.readFileSync(path.join(root, 'SymconJSLive', asset), 'utf8');
const start = bundle.indexOf('    this.transition = {');
const end = bundle.indexOf('    this.set = function', start);
assert.ok(start >= 0 && end > start);
function frame({ src = 25, des = 75, time = 1000, precision = 0.1, animate = true, min = 0, max = 100 } = {}) {
    const context = {
        config: { min, max, precision, duration: 0.1, 'stroke-dir': 'normal' },
        isStroke: true, length: 100, text: {}, path1: { attrs(style) { context.style = style; } }
    };
    vm.createContext(context);
    vm.runInContext(bundle.slice(start, end), context);
    context.transition.value = { src, des };
    context.transition.time.src = 0;
    const running = context.transition.handler(time, animate);
    return { value: context.text.textContent, dash: context.style['stroke-dasharray'], running };
}
for (const time of [100, 120, 500, 5000]) {
    assert.deepEqual(frame({ time }), { value: 75, dash: '75 26', running: false }, 'Late frames must finish at the target.');
    assert.equal(frame({ src: 75, des: 25, time }).value, 25, 'Descending animations must finish at the target.');
}
assert.deepEqual(frame({ time: 50 }), { value: 50, dash: '50 51', running: true }, 'Keep interpolation before the endpoint.');
assert.equal(frame({ des: 50.54, precision: 0.1 }).value, 50.5, 'Retain configured precision.');
assert.equal(frame({ des: 50.54, precision: 0 }).value, 51, 'Retain animated integer rounding.');
assert.equal(frame({ des: 50.54, precision: 0, animate: false }).value, 50.54, 'Retain immediate fractional values.');
assert.equal(frame({ des: 150 }).value, 100);
assert.equal(frame({ des: -25 }).value, 0);
assert.equal(frame({ src: -75, des: -25, min: -100, max: 0 }).value, -25);
console.log('Loading Bar animation endpoints, interpolation, precision and bounds verified.');
