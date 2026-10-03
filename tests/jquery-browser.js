// Optional browser contract check. Playwright is supplied by the caller.
// All HTTP requests are fulfilled locally or aborted; never contacts Symcon.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8').replace(/\r\n/g, '\n');
const chart = read('SymconJSLive/templates/Chart.html');
const form = read('SymconJSLive/templates/FormExample.html');
function extract(source, name, next) {
    const start = source.indexOf(`    function ${name}(`);
    const end = source.indexOf(next, start);
    assert.ok(start >= 0 && end > start, `Missing template function ${name}.`);
    return source.slice(start, end);
}
const templateFunctions = extract(chart, 'updateChartconfig', '    function connect(')
    + extract(chart, 'ReloadChart', '    async function UpdateConfiguration(')
    + extract(form, 'updateForm', '    function updateValue(');
const jqueryPath = chart.match(/src="(\/hook\/JSLive\/js\/jquery[^"\s]+)"/)[1]
    .replace('/hook/JSLive/', 'SymconJSLive/');
const dataset = index => ({ type: index ? 'bar' : 'line', label: `Series ${index}`, data: [{ x: 1700000000000, y: index + 1 }] });
const scenarios = [
    { name: 'forward', order: [0, 1], expected: [0, 1] },
    { name: 'reverse', order: [1, 0], expected: [0, 1] },
    { name: 'empty', order: [1, 0], expected: [1] },
    { name: 'http-error', order: [1, 0], expected: [1], error: true },
    { name: 'invalid-json', order: [0, 1], expected: [1], error: true },
    { name: 'zero-datasets', order: [], expected: [] }
];

async function check(browser, asset, version, scenario) {
    const context = await browser.newContext();
    const page = await context.newPage();
    const pending = new Map();
    const errors = [];
    const unexpectedRequests = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => { errors.push(dialog.message()); await dialog.dismiss(); });
    const config = { data_loadAsync: true, Var_List: scenario.order.map((_, i) => 100 + i),
        Period: 6, Relativ: true, ID_Period: 11, ID_Relativ: 12 };
    const axes = { AXES: { y: { type: 'linear' } }, XAXES: { type: 'realtime' } };
    await context.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin !== 'http://jslive.test' || route.request().method() !== 'GET') {
            unexpectedRequests.push(url.pathname); return route.abort();
        }
        if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body:
            '<!doctype html><div id="chart-container"><canvas id="myChart"></canvas></div><input id="label"><input id="level">' });
        if (url.pathname === '/probe') return route.fulfill({ json: { value: 42, text: 'Grüße & < >' } });
        if (url.pathname === '/hook/JSLive/getUpdate') {
            if (url.searchParams.has('loadConfig')) return route.fulfill({ json: { Config: config } });
            if (url.searchParams.has('loadAxes')) return route.fulfill({ json: axes });
            if (url.searchParams.has('id')) {
                const index = Number(url.searchParams.get('id'));
                pending.set(index, route);
                return;
            }
        }
        unexpectedRequests.push(url.pathname); return route.abort();
    });
    try {
        await page.goto('http://jslive.test/');
        await page.addScriptTag({ content: read(asset) });
        assert.equal(await page.evaluate(() => $.fn.jquery), version);
        await page.addScriptTag({ content: 'let myChart;\n' + templateFunctions });
        await page.evaluate(config => {
            Object.assign(window, {
                configuration: config, config_global: { DataMode: 1 },
                config_dataset: [], config_axes: {}, config_xaxes: {}, config_legend: {}, config_tooltips: {},
                config_title: { display: true, text: 'jQuery migration' },
                ChartDataLabels: {}, Get_WindowWidth: () => 800, Get_WindowHeight: () => 600,
                checkIsStreaming: () => ({ frameRate: 30 }), UpdateTooltipLabel: () => 'value',
                checkOffsetisSet: () => false, UpdateConfiguration() {}, PullNewData() {},
                update_vars: [11, 12], last_reload: 0, reloadGeneration: 0,
                reloadFullRequired: false, isReloading: false, pullMode: false,
                created: [], failures: [], completed: []
            });
            // Chart records the completed config; jQuery's transport and callbacks are real.
            window.Chart = function (canvas, config) {
                this.data = config.data; this.options = config.options; this.updates = 0;
                this.update = () => this.updates++;
                this.destroy = () => { this.destroyed = true; };
                created.push(this);
            };
            Chart.register = () => {};
            console.error = message => failures.push(String(message));
            $(document).ajaxComplete((event, xhr, settings) => {
                const id = new URL(settings.url, location.href).searchParams.get('id');
                if (id !== null) completed.push(Number(id));
            });
        }, config);
        const basics = await page.evaluate(async () => {
            await new Promise(resolve => $(document).ready(resolve));
            value = JSON.stringify({ label: 'Grüße & < >', level: 0 });
            updateForm(null);
            const loaded = await $.getJSON('/probe'); // jqXHR thenable, used by the HTMLBox loader.
            return { label: document.getElementById('label').value,
                level: document.getElementById('level').value, loaded };
        });
        assert.deepEqual(basics, { label: 'Grüße & < >', level: '0', loaded: { value: 42, text: 'Grüße & < >' } });
        for (const [stamp, full] of [[1, true], [2, false], [3, true]]) {
            pending.clear();
            await page.evaluate(({ stamp, full }) => { completed = []; ReloadChart(11, stamp, null, full); }, { stamp, full });
            if (scenario.order.length) {
                const deadline = Date.now() + 5000;
                while (pending.size < scenario.order.length && Date.now() < deadline) {
                    await new Promise(resolve => setTimeout(resolve, 10));
                }
                assert.equal(pending.size, scenario.order.length, 'All dataset requests must be issued.');
                for (const [position, index] of scenario.order.entries()) {
                    const route = pending.get(index);
                    if (index === 0 && scenario.name === 'http-error') {
                        await route.fulfill({ status: 503, contentType: 'text/plain', body: 'Expected fixture failure' });
                    } else if (index === 0 && scenario.name === 'invalid-json') {
                        await route.fulfill({ contentType: 'application/json', body: '{invalid' });
                    } else {
                        await route.fulfill({ json: { DATASETS: index === 0 && scenario.name === 'empty' ? [] : [dataset(index)] } });
                    }
                    await page.waitForFunction(index => completed.includes(index), index);
                    if (position < scenario.order.length - 1) {
                        assert.equal(await page.evaluate(() => isReloading), true, 'Do not finish after only one response.');
                        if (stamp === 1) assert.equal(await page.evaluate(() => created.length), 0);
                    }
                }
            }
            await page.waitForFunction(() => !isReloading && typeof myChart !== 'undefined');
            const state = await page.evaluate(() => ({ labels: myChart.data.datasets.map(d => d.label),
                count: created.length, updates: myChart.updates, oldDestroyed: !!created[0].destroyed,
                failures: failures.length, title: myChart.options.plugins.title.text }));
            assert.deepEqual(state, { labels: scenario.expected.map(i => `Series ${i}`), count: stamp === 3 ? 2 : 1,
                updates: stamp === 2 ? 1 : 0, oldDestroyed: stamp === 3,
                failures: scenario.error ? stamp : 0, title: 'jQuery migration' });
        }
        assert.deepEqual(errors, []);
        assert.deepEqual(unexpectedRequests, []);
        return { version, scenario: scenario.name, result: 'PASS' };
    } finally { await context.close(); }
}

async function run(browser) {
    const results = [];
    for (const [asset, version] of [['SymconJSLive/js/jquery.min.js', '3.6.0'], [jqueryPath, '4.0.0']]) {
        for (const scenario of scenarios) results.push(await check(browser, asset, version, scenario));
    }
    return { browser: browser.version(), results };
}
module.exports = run;
if (require.main === module) {
    (async () => {
        const { chromium } = require('playwright');
        const browser = await chromium.launch({ headless: true,
            ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? { executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE } : {}) });
        try { console.log(JSON.stringify(await run(browser), null, 2)); }
        finally { await browser.close(); }
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
