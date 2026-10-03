// Dependency-free contracts for the shared opt-in resize lifecycle and time inputs.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const listeners = new Map();
let pending, observed, disconnected = false;
const context = vm.createContext({
    configuration: { overrideWidth: 0, overrideHeight: 0 },
    window: { innerWidth: 940, innerHeight: 390,
        addEventListener: (name, fn) => listeners.set(name, fn),
        removeEventListener: name => listeners.delete(name) },
    document: { documentElement: {} },
    requestAnimationFrame: fn => { pending = fn; return 1; },
    cancelAnimationFrame: () => { pending = null; },
    ResizeObserver: class { constructor(fn) { observed = fn; } observe() {} disconnect() { disconnected = true; } }
});
vm.runInContext(read('SymconJSLive/js/util.js'), context);
const sizes = [];
const stop = context.JSLiveObserveSize(() => sizes.push([context.Get_WindowWidth(), context.Get_WindowHeight()]));
function flush() { const fn = pending; pending = null; if (fn) fn(); }
flush();
assert.deepEqual(sizes, [[940, 390]]);
for (const event of ['resize', 'orientationchange', 'pageshow']) assert.ok(listeners.has(event));
context.window.innerWidth = 480;
context.window.innerHeight = 220;
listeners.get('resize')(); observed(); flush();
assert.deepEqual(sizes.at(-1), [480, 220]);
observed(); flush();
assert.equal(sizes.length, 2, 'No observer feedback loop.');
context.configuration.overrideWidth = 600;
listeners.get('resize')(); flush();
assert.deepEqual(sizes.at(-1), [600, 220]);
context.window.innerWidth = 0;
context.window.innerHeight = 0;
listeners.get('resize')(); flush();
assert.equal(sizes.length, 3, 'Defer hidden frames.');
context.window.innerWidth = 940;
context.window.innerHeight = 390;
listeners.get('resize')(); flush();
assert.deepEqual(sizes.at(-1), [600, 390]);
stop();
assert.ok(disconnected);
assert.equal(listeners.size, 0);
for (const name of ['TimePicker1', 'TimePicker2', 'TimePicker3']) {
    const source = read(`SymconJSLive/templates/${name}.html`);
    const start = source.indexOf('    function unixTimeToString(');
    const end = source.indexOf('    function stringToUnixTime(', start);
    const test = vm.createContext({ Date });
    vm.runInContext(source.slice(start, end), test);
    for (const [month, hour, minute, expected] of [[0, 0, 0, '00:00'], [6, 9, 5, '09:05'], [6, 23, 59, '23:59']]) {
        const timestamp = new Date(2026, month, 15, hour, minute).getTime() / 1000;
        assert.equal(test.unixTimeToString(timestamp), expected, `${name}: local HH:mm`);
    }
}
console.log('Visualization sizing lifecycle and time formatting passed.');
