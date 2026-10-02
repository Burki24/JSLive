const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const prototype = path.join(__dirname, 'prototypes', 'realtime-window.js');
assert.ok(fs.existsSync(prototype), 'The isolated realtime prototype must exist.');
const RealtimeWindow = require(prototype);

function harness(duration = 60000) {
    let now = 120000;
    let nextId = 0;
    const frames = new Map();
    const timers = new Map();
    const listeners = new Map();
    const updates = [];
    const document = {
        hidden: false,
        addEventListener: (name, fn) => listeners.set(name, fn),
        removeEventListener: (name, fn) => { if (listeners.get(name) === fn) listeners.delete(name); }
    };
    const chart = {
        options: { scales: { x: { type: 'time' } } },
        data: { datasets: [{ data: [], label: 'Synthetic', borderColor: 'blue' }] },
        update: mode => updates.push(mode),
        getActiveElements: () => [], setActiveElements() {}
    };
    const env = {
        now: () => now, document,
        requestFrame: fn => { const id = ++nextId; frames.set(id, fn); return id; },
        cancelFrame: id => frames.delete(id),
        setInterval: fn => { const id = ++nextId; timers.set(id, fn); return id; },
        clearInterval: id => timers.delete(id)
    };
    const live = new RealtimeWindow(chart, { duration }, env);
    function frame(ms = 40) {
        now += ms;
        const pending = Array.from(frames.values());
        frames.clear();
        pending.forEach(fn => fn());
    }
    function maintenance(ms = 1000) {
        now += ms;
        Array.from(timers.values()).forEach(fn => fn());
    }
    function visibility(hidden) {
        document.hidden = hidden;
        if (listeners.has('visibilitychange')) listeners.get('visibilitychange')();
    }
    return { live, chart, updates, frames, timers, listeners, frame, maintenance, visibility,
        now: () => now, setNow: value => { now = value; } };
}

for (const duration of [60000, 3600000]) {
    const h = harness(duration);
    h.live.start();
    assert.equal(h.chart.options.scales.x.max, h.now());
    assert.equal(h.chart.options.scales.x.min, h.now() - duration);
    h.frame();
    assert.equal(h.chart.options.scales.x.max, h.now());
    assert.ok(h.updates.every(mode => mode === 'none'));
    h.live.destroy();
}

const batch = harness();
batch.live.start();
batch.updates.length = 0;
for (let i = 0; i < 100; i++) batch.live.invalidate();
assert.equal(batch.updates.length, 0, 'Incoming bursts must not render synchronously.');
batch.frame(1);
assert.equal(batch.updates.length, 0, 'Respect the drawing interval.');
batch.frame(40);
assert.equal(batch.updates.length, 1, 'Coalesce the burst into one public Chart update.');

const prune = harness();
prune.chart.data.datasets[0].data = [1000, 2000, 3000, 59000, 60000, 90000, 120000]
    .map((x, i) => ({ x, y: i, c: 2 }));
const kept = prune.chart.data.datasets[0].data[3];
prune.live.start();
assert.deepEqual(prune.chart.data.datasets[0].data.map(p => p.x), [3000, 59000, 60000, 90000, 120000]);
assert.equal(prune.chart.data.datasets[0].data[1], kept, 'Keep original point objects and metadata.');

const background = harness();
background.live.start();
background.visibility(true);
const hiddenUpdates = background.updates.length;
for (let i = 0; i < 180; i++) {
    background.chart.data.datasets[0].data.push({ x: background.now(), y: i });
    background.live.invalidate();
    background.maintenance();
}
assert.ok(background.chart.data.datasets[0].data.length <= 63, 'Retain only the live window plus boundary points.');
assert.equal(background.updates.length, hiddenUpdates, 'Hidden documents must not redraw.');
assert.equal(background.frames.size, 0, 'Hidden documents must not schedule animation frames.');
background.visibility(false);
assert.equal(background.chart.options.scales.x.max, background.now(), 'Catch up immediately after visibility returns.');

const paused = harness();
paused.chart.data.datasets[0].data = [60000, 90000, 120000].map(x => ({ x, y: 1 }));
paused.live.start();
paused.live.pause();
const frozenMax = paused.chart.options.scales.x.max;
for (let i = 0; i < 180; i++) {
    paused.chart.data.datasets[0].data.push({ x: paused.now(), y: 2 });
    paused.live.invalidate();
    paused.maintenance();
}
assert.equal(paused.chart.options.scales.x.max, frozenMax);
assert.ok(paused.chart.data.datasets[0].data.some(p => p.x === 90000), 'Preserve the frozen visible window.');
assert.ok(paused.chart.data.datasets[0].data.length <= 68, 'Do not retain the entire gap between paused and live windows.');
paused.live.resume();
assert.equal(paused.chart.options.scales.x.max, paused.now());
assert.ok(paused.chart.data.datasets[0].data.length <= 63);

const lifecycle = harness();
lifecycle.live.start();
lifecycle.live.start();
assert.equal(lifecycle.frames.size, 1);
assert.equal(lifecycle.timers.size, 1);
assert.equal(lifecycle.listeners.size, 1);
const staleCallback = Array.from(lifecycle.frames.values())[0];
lifecycle.live.destroy();
lifecycle.live.destroy();
assert.equal(lifecycle.frames.size + lifecycle.timers.size + lifecycle.listeners.size, 0);
const afterDestroy = lifecycle.updates.length;
staleCallback();
lifecycle.live.invalidate();
lifecycle.live.resume();
assert.equal(lifecycle.updates.length, afterDestroy);
assert.throws(() => lifecycle.live.start(), /destroyed/i);

const invalid = harness();
invalid.chart.data.datasets[0].data = [{ x: 90000, y: 1 }, { x: 80000, y: 2 }];
assert.throws(() => invalid.live.start(), /sorted/i, 'Reject unsupported unordered input instead of losing points.');
assert.equal(invalid.frames.size + invalid.timers.size + invalid.listeners.size, 0);

const failure = harness();
failure.live.start();
failure.chart.update = () => { throw new Error('Synthetic render failure'); };
assert.throws(() => failure.frame(), /Synthetic render failure/);
assert.equal(failure.frames.size + failure.timers.size + failure.listeners.size, 0, 'Stop all work after a render error.');

const duplicate = harness();
duplicate.live.start();
const secondOwner = new RealtimeWindow(duplicate.chart, { duration: 60000 });
assert.throws(() => secondOwner.start(), /already has/);
duplicate.live.destroy();

const selection = harness();
let active = [{ datasetIndex: 0, index: 0 }, { datasetIndex: 0, index: 4 }];
selection.chart.getActiveElements = () => active;
selection.chart.setActiveElements = elements => { active = elements; };
selection.chart.data.datasets[0].data = [1000, 2000, 3000, 90000, 120000].map(x => ({ x, y: 1 }));
selection.live.start();
assert.deepEqual(active, [{ datasetIndex: 0, index: 3 }], 'Remap retained selection and remove expired selection through public APIs.');
selection.live.destroy();

const optionArrays = harness();
optionArrays.chart.data.datasets[0].pointRadius = [1, 2];
assert.throws(() => optionArrays.live.start(), /option arrays/, 'Do not silently corrupt unsupported per-point styles.');

const hiddenStart = harness();
hiddenStart.visibility(true);
hiddenStart.live.start();
hiddenStart.live.pause();
hiddenStart.maintenance();
assert.equal(hiddenStart.updates.length, 0);
hiddenStart.visibility(false);
assert.equal(hiddenStart.updates.length, 0, 'Visibility must not override an explicit pause.');
hiddenStart.live.resume();
assert.equal(hiddenStart.chart.options.scales.x.max, hiddenStart.now());
hiddenStart.live.destroy();

for (const h of [batch, prune, background, paused]) h.live.destroy();
const template = fs.readFileSync(path.join(__dirname, '..', 'SymconJSLive', 'templates', 'Chart.html'), 'utf8');
assert.ok(template.includes('chartjs-plugin-streaming.min.js'), 'The production integration remains unchanged in the prototype phase.');
assert.ok(!template.includes('realtime-window.js'));
console.log('Realtime window prototype tests passed.');
