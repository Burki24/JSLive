const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const templatePath = path.join(__dirname, '..', 'SymconJSLive', 'templates', 'Chart.html');
const template = fs.readFileSync(templatePath, 'utf8').replace(/\r\n/g, '\n');
function extract(name, nextDeclaration) {
    const start = template.indexOf(`    function ${name}(`);
    const end = template.indexOf(nextDeclaration, start);
    assert.ok(start >= 0 && end > start, `Missing template function ${name}.`);
    return template.slice(start, end);
}
const util = fs.readFileSync(path.join(__dirname, '..', 'SymconJSLive', 'js', 'util.js'), 'utf8');
const helpersEnd = util.indexOf('Array.prototype.insert');
assert.ok(helpersEnd > 0, 'Missing matching helpers.');
const source = util.slice(0, helpersEnd)
    + extract('UpdateChart', '    function ReloadChart(')
    + extract('UpdateDate', '    function GetPeriodTimespan(')
    + extract('checkIsStreaming', '    function checkOffsetisSet(');
const now = Date.UTC(2024, 0, 2, 12, 0, 0);
class FixedDate extends Date {
    constructor(...args) { super(...(args.length ? args : [now])); }
    static now() { return now; }
}

// Run the actual template functions; Chart only records the public update mode.
// The vendored Streaming plugin is checked separately in a real browser.
function harness(type = 'realtime') {
    const updates = [];
    const dataset = { Variable: 101, offset: 0, highRes: 7, counter: false, yAxisID: 'y', data: [] };
    const context = vm.createContext({
        Date: FixedDate,
        configuration: { Now: true, Relativ: true, Period: 7, data_precision: 2, data_highResSteps: 1 },
        config_dataset: [dataset], config_axes: { y: {} }, isReloading: false,
        myChart: {
            data: { datasets: [dataset] }, options: { scales: { x: { type } } },
            update: mode => updates.push(mode)
        },
        console: { log() {}, error(error) { throw error; } }
    });
    vm.runInContext(source, context, { filename: templatePath });
    return { context, dataset, updates };
}

for (const relative of [true, false]) {
    for (const period of [0, 1, 2, 3, 4, 5, 6, 7, 99]) {
        const h = harness();
        h.context.configuration.Relativ = relative;
        h.context.configuration.Period = period;
        const streaming = h.context.checkIsStreaming();
        if (relative && (period === 6 || period === 7)) {
            assert.equal(streaming.frameRate, 30, 'Use the case-sensitive Streaming frameRate option.');
            assert.equal(Object.prototype.hasOwnProperty.call(streaming, 'framerate'), false);
        } else {
            assert.equal(streaming, false, 'Only relative hour/minute views enable Streaming.');
        }
    }
}

for (const type of ['realtime', 'time']) {
    const h = harness(type);
    h.context.UpdateChart(101, now / 1000, 42.259);
    assert.equal(h.dataset.data.length, 1);
    assert.equal(h.dataset.data[0].x, now);
    assert.equal(h.dataset.data[0].y, 42.25);
    assert.equal(h.updates.length, 1);
    assert.equal(h.updates[0], type === 'realtime' ? 'quiet' : undefined,
        'Realtime updates preserve scrolling with quiet; ordinary charts use the default mode.');
    h.context.UpdateChart(101, now / 1000 + 1, 43.5);
    assert.equal(h.dataset.data.length, 2);
    assert.equal(h.updates.length, 2);
}

for (const guard of ['historical', 'reloading', 'unknown variable']) {
    const h = harness();
    if (guard === 'historical') h.context.configuration.Now = false;
    if (guard === 'reloading') h.context.isReloading = true;
    h.context.UpdateChart(guard === 'unknown variable' ? 999 : 101, now / 1000, 5);
    assert.equal(h.dataset.data.length, 0, guard);
    assert.equal(h.updates.length, 0, guard);
}
const stale = harness();
stale.context.UpdateChart(101, now / 1000 - 61, 5);
assert.equal(stale.dataset.data.length, 1, 'Keep existing ingestion of older samples.');
assert.equal(stale.updates.length, 0, 'Keep the existing redraw recency gate.');

console.log('Chart Streaming integration tests passed.');
