// Execute the shipped template with real iro color models; mock only UI/network/time.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const iro = require(path.join(root, 'SymconJSLive/js/iro/5.5.0/iro.js'));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
let script = read('SymconJSLive/templates/ColorPicker.html').match(/<script>([\s\S]*?)<\/script>/)[1];
const data = [
    {Variable: 101, Mode: 'xy', Value: '{"x":0.64,"y":0.33}', ColorValue: 0xFF0000},
    {Variable: 102, Mode: 'xy', Value: '{"x":0.3,"y":0.6}', ColorValue: 0x00FF00},
    {Variable: 103, Mode: 'color', Value: 255},
    {Variable: 104, Mode: 'kelvin', Value: 4000},
    {Variable: 105, Mode: 'mired', Value: 250}
];
const settings = {DataUpdateRate: 0, manWidth: 0, layout_Direction: 'vertical', style_handleRadius: 8};
script = script.replace('{GLOBAL}', '{}').replace('{VARIABELS}', JSON.stringify(data))
    .replace('{LAYOUT}', '[]').replace('{CONFIG}', JSON.stringify(settings))
    .replaceAll('{INSTANCE}', '12345').replaceAll('{PASSWORD}', 'synthetic');
const pending = [], writes = [], handlers = {}, errors = [];
let picker, server = structuredClone(data), holdWrite, resumeWrite;
class Picker {
    constructor(selector, config) { picker = this; this.setColors(config.colors); }
    on(event, fn) { handlers[event] = fn; }
    get color() { return this.colors[this.activeIndex || 0]; }
    setActiveColor(index) { this.activeIndex = index; }
    setColors(colors) {
        this.activeIndex = 0;
        this.colors = colors.map((value, index) => {
            const color = new iro.Color(value); color.index = index;
            // Programmatic updates emit color:change but never input:change in iro.
            if (handlers['color:change']) handlers['color:change'](color);
            return color;
        });
    }
    resize() {}
}
const status = {textContent: ''};
const sandbox = vm.createContext({
    console: {log() {}, error: (...args) => errors.push(args)},
    iro: {...iro, ColorPicker: Picker},
    window: {innerWidth: 400, innerHeight: 400, detectLocation: () => ({protocol: 'http:', host: 'synthetic'})},
    document: {getElementById: () => status},
    JSLiveObserveSize() {},
    setTimeout: fn => pending.push(fn),
    URLSearchParams, Date, Promise,
    fetch: async url => {
        const parsed = new URL(url, 'http://synthetic');
        if (parsed.pathname.endsWith('/getData')) return {ok: true, json: async () => structuredClone(server)};
        writes.push({id: Number(parsed.searchParams.get('var')), value: parsed.searchParams.get('val')});
        if (holdWrite) { holdWrite = false; await new Promise(resolve => { resumeWrite = resolve; }); }
        return {ok: true, text: async () => 'OK'};
    },
    WebSocket: class { constructor() { sandbox.socket = this; } }
});
vm.runInContext(read('SymconJSLive/js/util.js'), sandbox);
sandbox.JSLiveObserveSize = () => {};
sandbox.window.detectLocation = () => ({protocol: 'http:', host: 'synthetic'});
vm.runInContext(script, sandbox);
const evaluate = source => vm.runInContext(source, sandbox);
async function settle() { for (let i = 0; i < 20; i++) await Promise.resolve(); }
async function tick() { const fn = pending.shift(); if (fn) fn(); await settle(); }
function input(index, rgb) {
    const color = new iro.Color(rgb); color.index = index;
    (handlers['input:change'] || handlers['color:change'])(color);
}
(async () => {
    assert.equal(picker.colors.length, 5, 'xy handles must not shift subsequent lamp indices');
    assert.equal(picker.colors[0].hexString.toLowerCase(), '#ff0000');
    assert.equal(picker.colors[1].hexString.toLowerCase(), '#00ff00');
    assert.equal(picker.colors[2].hexString.toLowerCase(), '#0000ff');
    assert.equal(writes.length, 0, 'Initialization is read-only');
    evaluate('connect()');
    server[0].Value = '{"x":0.15,"y":0.06}'; server[0].ColorValue = 255;
    sandbox.socket.onmessage({data: JSON.stringify({Message: 10603, SenderID: 101, Data: [server[0].Value]})});
    await settle();
    assert.equal(picker.colors[0].hexString.toLowerCase(), '#0000ff', 'WebSocket xy values must be normalized by the backend');
    assert.equal(writes.length, 0, 'External updates must never echo back to devices');
    server[1].Value = '{"x":0.3127,"y":0.329}'; server[1].ColorValue = 0xFFFFFF;
    await evaluate('RefreshColorData()');
    assert.equal(picker.colors[1].hexString.toLowerCase(), '#ffffff', 'Pull data must use the flat variable contract');
    input(1, '#ff0000');
    await tick();
    assert.deepEqual(writes, [{id: 102, value: '16711680'}]);
    holdWrite = true;
    input(0, '#00ff00');
    await tick();
    input(0, '#0000ff');
    input(2, '#ffffff');
    resumeWrite(); await settle(); await tick();
    assert.deepEqual(writes.slice(1), [{id: 101, value: '65280'}, {id: 101, value: '255'}, {id: 103, value: '16777215'}], 'Edits during an in-flight request must not be discarded or sent to another handle');
    server[0].ColorValue = null; server[0].Error = 'Invalid xy color';
    await evaluate('RefreshColorData()');
    assert.equal(picker.colors.length, 5);
    assert.match(status.textContent, /Invalid xy color/);
    const before = writes.length;
    input(0, '#ff0000'); await tick();
    assert.equal(writes.length, before, 'An unreadable xy handle cannot send a fabricated color');
    server[0].ColorValue = 0xFF0000; server[0].Error = '';
    await evaluate('RefreshColorData()');
    assert.equal(status.textContent, '');
    assert.deepEqual(errors, []);
    console.log('ColorPicker xy handles, WebSocket/pull reads, no feedback writes and queued edits verified.');
})().catch(error => { console.error(error); process.exitCode = 1; });
