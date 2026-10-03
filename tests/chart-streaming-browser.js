// Optional integration check: caller provides Playwright and a Chromium browser.
// Loads the actual template assets/functions, but uses local RPC fixtures only.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { createHash } = require('node:crypto');

const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const template = read('SymconJSLive/templates/Chart.html').replace(/\r\n/g, '\n');
const assets = [...template.matchAll(/<script\b[^>]*src="(\/hook\/JSLive\/js\/[^"\s]+)"/g)]
    .map(match => match[1].replace('/hook/JSLive/', 'SymconJSLive/'));
function extract(name, next) {
    const start = template.indexOf(`    function ${name}(`);
    const end = template.indexOf(next, start);
    assert.ok(start >= 0 && end > start, `Missing template function ${name}.`);
    return template.slice(start, end);
}
const util = read('SymconJSLive/js/util.js');
assert.ok(util.indexOf('Array.prototype.insert') > 0);
const source = util.slice(0, util.indexOf('Array.prototype.insert'))
    + extract('updateChartconfig', '    function connect(')
    + extract('UpdateChart', '    function ReloadChart(')
    + extract('ReloadChart', '    async function UpdateConfiguration(')
    + extract('UpdateDate', '    function GetPeriodTimespan(')
    + extract('checkIsStreaming', '    function bootUp(');

async function check(browser, legacy, timezoneId, width) {
    const context = await browser.newContext({ viewport: { width, height: 720 }, timezoneId });
    await context.route('**/*', route => route.abort()); // Never contact Symcon or a CDN.
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('dialog', async dialog => { errors.push(dialog.message()); await dialog.dismiss(); });
    try {
        await page.clock.install({ time: new Date('2024-01-02T12:00:00Z') });
        await page.clock.pauseAt(new Date('2024-01-02T12:00:01Z'));
        await page.setContent('<div id="chart-container"><canvas id="myChart"></canvas></div>');
        for (let asset of assets) {
            if (asset.endsWith('/util.js')) continue; // Only matching helpers; no transport bootstrap.
            if (legacy && asset.includes('/streaming/')) asset = 'SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js';
            await page.addScriptTag({ content: read(asset) });
        }
        await page.addScriptTag({ content: 'let myChart;\n' + source });
        await page.evaluate(() => {
            Object.assign(window, {
                config_global: { DataMode: 0 }, config_dataset: [], config_axes: {}, config_xaxes: {},
                config_legend: { display: true }, config_title: { display: false }, config_tooltips: { enabled: true },
                configuration: { Now: true, Relativ: true, Period: 7, ID_Period: 11, ID_Relativ: 12,
                    data_loadAsync: false, data_precision: 2, data_highResSteps: 1,
                    animation_duration: 0, animation_easing: 'linear', datalabels_fontSize: 12,
                    datalabels_fontFamily: 'Arial', datalabels_fontColor: '#111' },
                update_vars: [11, 12], last_reload: 0, reloadGeneration: 0,
                reloadFullRequired: false, isReloading: false, pullMode: false,
                Get_WindowWidth: () => window.innerWidth, Get_WindowHeight: () => window.innerHeight,
                // Background transport/configuration loops are deliberately outside this fixture.
                UpdateConfiguration() {}, PullNewData() {}, draws: 0, labels: 0
            });
            const fillText = CanvasRenderingContext2D.prototype.fillText;
            CanvasRenderingContext2D.prototype.fillText = function (text, ...args) {
                if (String(text).endsWith(' units')) window.labels++;
                return fillText.call(this, text, ...args);
            };
            Chart.register({ id: 'integrationProbe', afterDraw: () => window.draws++ });
            $.getJSON = (url, success) => {
                const realtime = configuration.Relativ && [6, 7].includes(configuration.Period);
                const duration = configuration.Period === 6 ? 3600000 : 60000;
                success({ Config: { ...configuration },
                    AXES: { y: { type: 'linear', min: 0, max: 60, Prefix: '', Suffix: ' units' } },
                    XAXES: { type: realtime ? 'realtime' : 'time',
                        time: { tooltipFormat: 'DD.MM.YYYY HH:mm:ss' },
                        ...(realtime ? { realtime: { duration, refresh: 100, delay: 0 } }
                            : { min: Date.now() - duration, max: Date.now() }) },
                    DATASETS: ['line', 'bar'].map((type, index) => ({
                        type, label: type, Variable: 101 + index, offset: 0, highRes: 7,
                        counter: false, yAxisID: 'y', borderColor: index ? '#dc2626' : '#2563eb',
                        backgroundColor: index ? '#dc262680' : '#2563eb80',
                        datalabels: { display: true, showSuffix: true, useBackgroundColor: false,
                            BackgroundColor: 'transparent', useBorderColor: false, BorderColor: 'transparent' },
                        data: [-50000, -40000, -30000, -10000].map((age, i) => ({ x: Date.now() + age, y: 20 + i + index }))
                    })) });
            };
            ReloadChart(11, 1, null, true);
        });
        await page.clock.runFor(100);
        assert.deepEqual(await page.evaluate(() => myChart.data.datasets.map(d => d.type)), ['line', 'bar']);
        assert.equal(await page.evaluate(() => myChart.scales.x.type), 'realtime');
        assert.ok(await page.evaluate(() => labels > 0), 'Actual template Datalabels formatter must draw.');
        const initial = await page.evaluate(() => document.querySelector('canvas').toDataURL());
        const initialHash = createHash('sha256').update(initial).digest('hex');
        const max = await page.evaluate(() => myChart.scales.x.max);
        await page.evaluate(() => {
            UpdateChart(101, Date.now() / 1000, 42.259);
            UpdateChart(102, Date.now() / 1000, 43.5);
        });
        await page.clock.runFor(200);
        assert.deepEqual(await page.evaluate(() => myChart.data.datasets.map(d => d.data.at(-1).y)), [42.25, 43.5]);
        assert.ok(await page.evaluate(() => myChart.scales.x.max) > max);
        const tooltip = await page.evaluate(() => {
            myChart.tooltip.setActiveElements([{ datasetIndex: 0, index: 4 }], { x: 100, y: 100 });
            myChart.update('quiet');
            return { title: myChart.tooltip.title, lines: myChart.tooltip.body[0].lines };
        });
        assert.deepEqual(tooltip.lines, ['line: 42.25 units']);
        assert.deepEqual(tooltip.title, [`02.01.2024 ${timezoneId === 'UTC' ? '12' : '13'}:00:01`]);
        await page.evaluate(() => { myChart.options.scales.x.realtime.pause = true; myChart.update('quiet'); });
        const frozen = await page.evaluate(() => myChart.scales.x.max);
        await page.clock.runFor(1200);
        assert.equal(await page.evaluate(() => myChart.scales.x.max), frozen);
        await page.evaluate(() => { myChart.options.scales.x.realtime.pause = false; myChart.update('quiet'); });
        await page.clock.runFor(200);
        assert.ok(await page.evaluate(() => myChart.scales.x.max) > frozen);
        await page.evaluate(() => {
            myChart.tooltip.setActiveElements([], { x: 0, y: 0 });
            myChart.data.datasets.forEach(d => {
                d.data = [-90000, -80000, -70000, -1000, 0].map((age, i) => ({ x: Date.now() + age, y: 20 + i }));
            });
            myChart.update('quiet');
            myChart.setActiveElements([{ datasetIndex: 0, index: 4 }]);
            myChart.tooltip.setActiveElements([{ datasetIndex: 0, index: 4 }], { x: 100, y: 100 });
        });
        await page.clock.runFor(200);
        assert.deepEqual(await page.evaluate(() => myChart.data.datasets.map(d => d.data.length)), [4, 4]);
        if (!legacy) {
            assert.deepEqual(await page.evaluate(() => myChart.getActiveElements().map(e => e.index)), [3]);
            assert.deepEqual(await page.evaluate(() => myChart.tooltip.body[0].lines), ['line: 24 units']);
            assert.equal(await page.evaluate(() => myChart.tooltip.dataPoints[0].raw.y), 24);
        }
        for (const [stamp, period, relative, expected] of [[2, 5, false, 'time'], [3, 6, true, 'realtime'], [4, 7, true, 'realtime']]) {
            const result = await page.evaluate(({ stamp, period, relative }) => {
                const old = myChart;
                configuration.Relativ = relative;
                ReloadChart(11, stamp, period, true);
                return { oldDestroyed: old.canvas === null, instances: Object.keys(Chart.instances).length, type: myChart.scales.x.type };
            }, { stamp, period, relative });
            assert.deepEqual(result, { oldDestroyed: true, instances: 1, type: expected });
            await page.clock.runFor(150);
        }
        // Real Chart.js scales must follow async historical navigation without
        // destroying the chart or retaining the previous day's suggested range.
        await page.evaluate(() => {
            configuration.data_loadAsync = true;
            configuration.Var_List = [101, 102];
            configuration.Period = 5;
            configuration.Relativ = false;
            configuration.Now = false;
            $.getJSON = (url, success) => {
                const query = new URL(url, 'http://test.invalid').searchParams;
                const start = window.historicalStart;
                if (query.has('loadConfig')) success({ Config: { ...configuration } });
                else if (query.has('loadAxes')) success({
                    AXES: { y: { type: 'linear', min: 0, max: 60 } },
                    XAXES: { type: 'time', suggestedMin: start, suggestedMax: start + 86400000 - 1 }
                });
                else success({ DATASETS: [{
                    type: query.get('id') === '0' ? 'line' : 'bar', yAxisID: 'y',
                    data: [{ x: start + 3600000, y: 25 }, { x: start + 7200000, y: 30 }]
                }] });
                return { fail() { return this; }, always(callback) { callback(); return this; } };
            };
        });
        for (const [index, start] of [Date.UTC(2024, 0, 3), Date.UTC(2024, 0, 2), Date.UTC(2024, 0, 3)].entries()) {
            const state = await page.evaluate(({ index, start }) => {
                const old = myChart;
                window.historicalStart = start;
                ReloadChart(11, 10 + index, null, index === 0);
                return { reused: old === myChart, instances: Object.keys(Chart.instances).length,
                    min: myChart.scales.x.min, max: myChart.scales.x.max, type: myChart.scales.x.type,
                    datasets: myChart.data.datasets.length, loading: isReloading };
            }, { index, start });
            assert.deepEqual(state, { reused: index !== 0, instances: 1,
                min: start, max: start + 86400000 - 1, type: 'time', datasets: 2, loading: false });
            await page.clock.runFor(150);
        }
        // Hold old dataset replies until the new selection has rendered. Use
        // real Chart.js to catch range expansion from stale points as well.
        const overlapping = await page.evaluate(() => {
            const originalGetJSON = $.getJSON;
            const held = [];
            const previous = myChart;
            $.getJSON = (url, success) => {
                if (!new URL(url, 'http://test.invalid').searchParams.has('id')) {
                    return originalGetJSON(url, success);
                }
                let payload;
                originalGetJSON(url, data => { payload = data; });
                const request = { finish() { success(payload); this.complete(); },
                    fail() { return this; }, always(callback) { this.complete = callback; return this; } };
                held.push(request);
                return request;
            };
            window.historicalStart = Date.UTC(2024, 0, 4);
            ReloadChart(11, 20);
            window.historicalStart = Date.UTC(2024, 0, 5);
            ReloadChart(11, 21);
            held[3].finish(); held[2].finish(); // latest selection, reverse dataset order
            held[0].finish(); held[1].finish(); // obsolete replies arrive last
            $.getJSON = originalGetJSON;
            return { reused: myChart === previous, instances: Object.keys(Chart.instances).length,
                min: myChart.scales.x.min, max: myChart.scales.x.max,
                points: myChart.data.datasets.map(d => d.data[0].x), loading: isReloading };
        });
        const latestDay = Date.UTC(2024, 0, 5);
        assert.deepEqual(overlapping, { reused: true, instances: 1,
            min: latestDay, max: latestDay + 86399999,
            points: [latestDay + 3600000, latestDay + 3600000], loading: false });
        await page.evaluate(() => myChart.destroy());
        const draws = await page.evaluate(() => window.draws);
        await page.clock.runFor(1200);
        assert.equal(await page.evaluate(() => window.draws), draws, 'No render after destruction.');
        assert.equal(await page.evaluate(() => Object.keys(Chart.instances).length), 0);
        assert.deepEqual(errors, []);
        return { version: legacy ? '3.1.0' : '3.6.0', timezoneId, width, initialHash, result: 'PASS' };
    } finally { await context.close(); }
}

async function run(browser) {
    const results = [];
    for (const timezone of ['UTC', 'Europe/Berlin']) {
        for (const width of [1024, 390]) {
            const before = await check(browser, true, timezone, width);
            const after = await check(browser, false, timezone, width);
            assert.equal(after.initialHash, before.initialHash, 'Same initial canvas before and after the asset switch.');
            results.push(before, after);
        }
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
