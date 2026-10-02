// Optional browser experiment; Playwright is provided by the caller, not installed by this script.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const scripts = [
    'SymconJSLive/js/chartjs/4.5.1/chart.umd.min.js',
    'SymconJSLive/js/moment/2.31.0/moment.min.js',
    'SymconJSLive/js/chartjs/plugins/moment/1.0.1/chartjs-adapter-moment.min.js'
].map(read);

async function openChart(browser, mode, options = {}) {
    const context = await browser.newContext({ viewport: { width: 1024, height: 768 }, timezoneId: 'Europe/Berlin' });
    await context.route('**/*', route => route.abort()); // No Symcon or external requests.
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('dialog', async dialog => { errors.push('Unexpected dialog'); await dialog.dismiss(); });
    if (options.clock) {
        await page.clock.install({ time: new Date('2024-01-02T12:00:00Z') });
        await page.clock.pauseAt(new Date('2024-01-02T12:00:01Z'));
    }
    await page.setContent('<!doctype html><html><body style="margin:16px;background:white"><canvas id="chart" width="900" height="500"></canvas></body></html>');
    for (const content of scripts) await page.addScriptTag({ content });
    await page.addScriptTag({ content: read(mode === 'own' ? 'tests/prototypes/realtime-window.js' : 'SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js') });
    await page.addScriptTag({ content: read('SymconJSLive/js/chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js') });
    await page.evaluate(({ mode, options }) => {
        window.probe = { updates: 0, draws: 0, labels: 0 };
        const fill = CanvasRenderingContext2D.prototype.fillText;
        CanvasRenderingContext2D.prototype.fillText = function (text, ...args) {
            if (String(text).startsWith('Value:')) probe.labels++;
            return fill.call(this, text, ...args);
        };
        const duration = options.duration || 60000;
        const count = options.points || 20;
        const datasets = Array.from({ length: options.datasets || 2 }, (_, datasetIndex) => ({
            type: datasetIndex % 2 ? 'bar' : 'line', label: `Series ${datasetIndex + 1}`,
            borderColor: datasetIndex % 2 ? '#dc2626' : '#2563eb',
            backgroundColor: datasetIndex % 2 ? '#dc262680' : '#2563eb80',
            pointRadius: options.points ? 0 : 3,
            data: Array.from({ length: count }, (_, i) => ({
                x: Date.now() - duration + (i + 1) * duration / count,
                y: (Math.sin(i / 5) + 2) * (datasetIndex + 1)
            }))
        }));
        Chart.register(ChartDataLabels);
        window.testChart = new Chart(document.getElementById('chart'), {
            type: 'line', data: { datasets },
            options: {
                responsive: false, animation: false,
                plugins: {
                    streaming: mode === 'plugin' ? { frameRate: 30 } : false,
                    datalabels: { display: !options.points, formatter: point => `Value:${point.y.toFixed(1)}` }
                },
                scales: {
                    x: mode === 'plugin' ? { type: 'realtime', realtime: { duration } } :
                        { type: 'time', min: Date.now() - duration, max: Date.now() },
                    y: { type: 'linear' }
                }
            },
            plugins: [{ id: 'probe', beforeUpdate: () => probe.updates++, afterDraw: () => probe.draws++ }]
        });
        if (mode === 'own') {
            window.live = new JSLiveRealtimeWindow(testChart, { duration });
            live.start();
        }
        window.pushSample = () => {
            testChart.data.datasets.forEach((dataset, index) => dataset.data.push({ x: Date.now(), y: 4 + index }));
            if (mode === 'own') live.invalidate();
            else testChart.update('quiet');
        };
    }, { mode, options });
    return { context, page, errors };
}

async function run(browser, { benchmark = false, capture = false } = {}) {
    const results = { browser: browser.version(), behavior: [], benchmarks: [] };
    for (const mode of ['plugin', 'own']) {
        const h = await openChart(browser, mode, { clock: true });
        try {
            const initialMax = await h.page.evaluate(() => testChart.scales.x.max);
            await h.page.evaluate(() => pushSample());
            await h.page.clock.runFor(1200);
            const state = await h.page.evaluate(() => ({
                max: testChart.scales.x.max, values: testChart.data.datasets.map(d => d.data.at(-1).y),
                labels: probe.labels, type: testChart.scales.x.type
            }));
            assert.ok(state.max > initialMax);
            assert.deepEqual(state.values, [4, 5]);
            assert.ok(state.labels > 0);
            const tooltip = await h.page.evaluate(() => {
                testChart.tooltip.setActiveElements([{ datasetIndex: 0, index: 19 }], { x: 450, y: 250 });
                testChart.update('none');
                return testChart.tooltip.dataPoints.length;
            });
            assert.equal(tooltip, 1);
            await h.page.evaluate(mode => {
                if (mode === 'own') live.pause();
                else { testChart.options.scales.x.realtime.pause = true; testChart.update('quiet'); }
            }, mode);
            const frozen = await h.page.evaluate(() => testChart.scales.x.max);
            await h.page.clock.runFor(1200);
            assert.equal(await h.page.evaluate(() => testChart.scales.x.max), frozen);
            await h.page.evaluate(mode => {
                if (mode === 'own') live.resume();
                else { testChart.options.scales.x.realtime.pause = false; testChart.update('quiet'); }
            }, mode);
            await h.page.clock.runFor(200);
            assert.ok(await h.page.evaluate(() => testChart.scales.x.max) > frozen);
            // Same data, same boundary contract: two points before the visible window.
            await h.page.evaluate(() => {
                testChart.tooltip.setActiveElements([], { x: 0, y: 0 });
                testChart.data.datasets.forEach(d => {
                    d.data = [-90000, -80000, -70000, -1000, 0].map((age, i) => ({ x: Date.now() + age, y: i }));
                });
                testChart.update('none');
                testChart.setActiveElements([{ datasetIndex: 0, index: 4 }]);
                testChart.tooltip.setActiveElements([{ datasetIndex: 0, index: 4 }], { x: 850, y: 100 });
            });
            await h.page.clock.runFor(1200);
            assert.deepEqual(await h.page.evaluate(() => testChart.data.datasets.map(d => d.data.length)), [4, 4]);
            if (mode === 'own') {
                assert.deepEqual(await h.page.evaluate(() => testChart.getActiveElements().map(e => e.index)), [3]);
                assert.equal(await h.page.evaluate(() => testChart.tooltip.dataPoints[0].raw.y), 4);
            }
            if (capture && mode === 'own') results.screenshot = await h.page.locator('canvas').screenshot();
            await h.page.evaluate(mode => { if (mode === 'own') live.destroy(); testChart.destroy(); }, mode);
            const draws = await h.page.evaluate(() => probe.draws);
            await h.page.clock.runFor(1200);
            assert.equal(await h.page.evaluate(() => probe.draws), draws, 'No rendering after destruction.');
            assert.equal(await h.page.evaluate(() => Object.keys(Chart.instances).length), 0);
            assert.deepEqual(h.errors, []);
            results.behavior.push({ mode, result: 'PASS', scope: 'mixed chart, values, labels, tooltip, scrolling, pause/resume, pruning, destroy' });
        } finally { await h.context.close(); }
    }
    if (benchmark) {
        // Same workloads, real clock, visible headless page, two independent trials.
        // TaskDuration is browser main-thread time, not system-wide CPU usage.
        for (const points of [1000, 5000]) {
            for (let trial = 1; trial <= 2; trial++) {
                for (const mode of ['plugin', 'own']) {
                    const h = await openChart(browser, mode, { points, datasets: 4 });
                    try {
                        const cdp = await h.context.newCDPSession(h.page);
                        await cdp.send('Performance.enable');
                        await h.page.evaluate(() => { window.feed = setInterval(pushSample, 1000); });
                        const before = await cdp.send('Performance.getMetrics');
                        const start = Date.now();
                        await h.page.waitForTimeout(2000);
                        const after = await cdp.send('Performance.getMetrics');
                        const elapsed = Date.now() - start;
                        const metric = data => data.metrics.find(m => m.name === 'TaskDuration').value;
                        const taskMs = (metric(after) - metric(before)) * 1000;
                        results.benchmarks.push({ mode, pointsPerDataset: points, datasets: 4, trial,
                            elapsedMs: elapsed, taskMs: Math.round(taskMs), mainThreadPercent: Math.round(taskMs / elapsed * 1000) / 10 });
                        await h.page.evaluate(mode => { clearInterval(feed); if (mode === 'own') live.destroy(); testChart.destroy(); }, mode);
                        assert.deepEqual(h.errors, []);
                    } finally { await h.context.close(); }
                }
            }
        }
    }
    return results;
}

module.exports = run;
if (require.main === module) {
    (async () => {
        const { chromium } = require('playwright');
        const browser = await chromium.launch({ headless: true,
            ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? { executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE } : {}) });
        try { console.log(JSON.stringify(await run(browser, { benchmark: true }), null, 2)); }
        finally { await browser.close(); }
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
