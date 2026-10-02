const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const templatePath = path.join(__dirname, '..', 'SymconJSLive', 'templates', 'Chart.html');
const template = fs.readFileSync(templatePath, 'utf8').replace(/\r\n/g, '\n');
function extract(startName, nextDeclaration) {
    const start = template.indexOf(`    function ${startName}(`);
    const end = template.indexOf(nextDeclaration, start);
    assert.ok(start >= 0 && end > start, `Missing template function ${startName}.`);
    return template.slice(start, end);
}
const source = extract('updateChartconfig', '    function connect(')
    + extract('ReloadChart', '    async function UpdateConfiguration(');

// Control only the HTTP completion order; execute the original template logic.
// Chart is a config receiver here; real rendering is checked in the browser.
function harness(count, asynchronous = true) {
    const pending = [];
    const created = [];
    const errors = [];
    const configuration = {
        data_loadAsync: asynchronous, Var_List: Array.from({ length: count }, (_, i) => i + 100),
        Period: 6, Relativ: true, ID_Period: 11, ID_Relativ: 12
    };
    function Chart(canvas, config) {
        assert.ok(config, 'Chart must receive its complete configuration.');
        this.config = config;
        this.data = config.data;
        this.options = config.options;
        this.updates = 0;
        this.update = () => { this.updates++; };
        this.destroy = () => { this.destroyed = true; };
        created.push(this);
    }
    Chart.register = () => {};
    const context = vm.createContext({
        configuration, Chart, ChartDataLabels: {}, Date,
        document: { getElementById: () => ({ style: {} }) },
        Get_WindowWidth: () => 800, Get_WindowHeight: () => 600,
        checkIsStreaming: () => ({ frameRate: 30 }),
        UpdateTooltipLabel: () => 'value',
        checkOffsetisSet: () => false, UpdateConfiguration() {}, PullNewData() {},
        console: { log() {}, error: message => errors.push(message) },
        alert(message) { throw new Error(String(message)); },
        config_global: { DataMode: 1 }, config_dataset: [], config_axes: {}, config_xaxes: {},
        config_legend: {}, config_title: { display: true, text: 'Test chart' }, config_tooltips: {},
        update_vars: [11, 12], last_reload: 0, isReloading: false, pullMode: false,
        $: {
            getJSON(url, success) {
                const request = {
                    url, success, failures: [], completions: [],
                    fail(callback) { this.failures.push(callback); return this; },
                    always(callback) { this.completions.push(callback); return this; }
                };
                pending.push(request);
                return request;
            }
        }
    });
    vm.runInContext(source, context, { filename: templatePath });
    function reply(parameter, data, failed = false) {
        const [key, value] = parameter.split('=');
        const index = pending.findIndex(request => {
            const params = new URL(request.url, 'http://test.invalid').searchParams;
            return params.has(key) && (value === undefined || params.get(key) === value);
        });
        assert.ok(index >= 0, `Missing request ${parameter}.`);
        const request = pending.splice(index, 1)[0];
        if (failed) request.failures.forEach(callback => callback());
        else request.success(data);
        request.completions.forEach(callback => callback());
    }
    const axes = () => ({ AXES: { y: { type: 'linear' } }, XAXES: { type: 'realtime' } });
    function begin(stamp = 1, full = true) {
        context.ReloadChart(11, stamp, null, full);
        if (asynchronous) {
            reply('loadConfig', { Config: configuration });
            reply('loadAxes', axes());
        }
    }
    return { context, created, errors, pending, reply, begin, axes, configuration };
}

function dataset(index) {
    return { label: `Series ${index}`, type: index === 0 ? 'line' : 'bar', data: [{ x: 1700000000000, y: index + 1 }] };
}
function assertChart(h, indices) {
    const c = h.context.myChart;
    assert.ok(c, 'A chart must exist after all requests settle.');
    assert.equal(c.options.scales.x.type, 'realtime');
    assert.equal(c.options.plugins.title.text, 'Test chart');
    assert.equal(c.options.plugins.tooltip.callbacks.label, h.context.UpdateTooltipLabel);
    assert.deepEqual(Array.from(c.data.datasets, d => d.label), indices.map(i => `Series ${i}`));
    assert.equal(h.context.isReloading, false);
}

const orders = [[1, 0], [0, 1], [0, 1, 2], [0, 2, 1], [1, 0, 2], [1, 2, 0], [2, 0, 1], [2, 1, 0]];
for (const order of orders) {
    const h = harness(order.length);
    h.begin();
    order.forEach((index, position) => {
        h.reply(`id=${index}`, { DATASETS: [dataset(index)] });
        if (position < order.length - 1) {
            assert.equal(h.created.length, 0, 'Do not initialize with incomplete dataset slots.');
            assert.equal(h.context.isReloading, true);
        }
    });
    assertChart(h, Array.from({ length: order.length }, (_, i) => i));
    assert.equal(h.created.length, 1);
    assert.equal(h.context.myChart.config.type, 'line');
    // A later partial reload updates the existing chart once, in configured order.
    h.begin(2, false);
    order.slice().reverse().forEach(i => h.reply(`id=${i}`, { DATASETS: [dataset(i)] }));
    assertChart(h, Array.from({ length: order.length }, (_, i) => i));
    assert.equal(h.created.length, 1);
    assert.equal(h.context.myChart.updates, 1);
    const previous = h.context.myChart;
    h.begin(3, true);
    order.forEach(i => h.reply(`id=${i}`, { DATASETS: [dataset(i)] }));
    assertChart(h, Array.from({ length: order.length }, (_, i) => i));
    assert.equal(h.created.length, 2);
    assert.equal(previous.destroyed, true);
}

for (const failed of [false, true]) {
    const h = harness(2);
    h.begin();
    h.reply('id=1', { DATASETS: [dataset(1)] });
    h.reply('id=0', { DATASETS: [] }, failed);
    assertChart(h, [1]);
    assert.equal(h.errors.length, failed ? 1 : 0);
}
for (const count of [0, 1]) {
    const h = harness(count);
    h.begin();
    if (count) h.reply('id=0', { DATASETS: [] });
    assertChart(h, []);
}
const sync = harness(2, false);
sync.begin();
sync.reply('Instance', { Config: sync.configuration, ...sync.axes(), DATASETS: [dataset(0), dataset(1)] });
assertChart(sync, [0, 1]);
console.log('JSLive Chart asynchronous loading verified (13 scenarios, including reloads).');
