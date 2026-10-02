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
    await page.addScriptTag({ content: options.controllerSource || read(mode === 'own' ? 'tests/prototypes/realtime-window.js' : 'SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js') });
    await page.addScriptTag({ content: read('SymconJSLive/js/chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js') });
    await page.evaluate(({ mode, options }) => {
        window.probe = { updates: 0, draws: 0, labels: 0, frames: [], updateMs: [] };
        const fill = CanvasRenderingContext2D.prototype.fillText;
        CanvasRenderingContext2D.prototype.fillText = function (text, ...args) {
            if (String(text).startsWith('Value:')) probe.labels++;
            return fill.call(this, text, ...args);
        };
        const duration = options.duration || 60000;
        const count = options.points || 20;
        const initialTime = Date.now();
        const datasets = Array.from({ length: options.datasets || 2 }, (_, datasetIndex) => ({
            type: datasetIndex % 2 ? 'bar' : 'line', label: `Series ${datasetIndex + 1}`,
            borderColor: datasetIndex % 2 ? '#dc2626' : '#2563eb',
            backgroundColor: datasetIndex % 2 ? '#dc262680' : '#2563eb80',
            pointRadius: options.points ? 0 : 3,
            data: Array.from({ length: count }, (_, i) => ({
                x: initialTime - duration + (i + 1) * duration / count,
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
            plugins: [{ id: 'probe',
                beforeUpdate: () => { probe.updates++; probe.updateStart = performance.now(); },
                afterUpdate: () => probe.updateMs.push(performance.now() - probe.updateStart),
                afterDraw: () => { probe.draws++; probe.frames.push(performance.now()); }
            }]
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

async function measure(browser, mode, options = {}) {
    const h = await openChart(browser, mode, { points: 1000, datasets: 4, ...options });
    try {
        const cdp = await h.context.newCDPSession(h.page);
        await cdp.send('Performance.enable');
        await h.page.waitForTimeout(options.warmupMs || 1000);
        const initial = await h.page.evaluate(measureMs => {
            probe.frames = []; probe.updateMs = []; probe.updates = 0;
            window.received = 0;
            // A fixed number of scheduled arrivals avoids an extra boundary sample
            // in a faster/slower variant. All modes receive exactly the same count.
            window.feed = [];
            for (let ms = 500; ms < measureMs; ms += 1000) {
                feed.push(setTimeout(() => { pushSample(); received++; }, ms));
            }
            return { time: performance.now(), points: testChart.data.datasets.map(d => d.data.length), received };
        }, options.measureMs || 3000);
        const before = await cdp.send('Performance.getMetrics');
        await h.page.waitForTimeout(options.measureMs || 3000);
        const after = await cdp.send('Performance.getMetrics');
        const final = await h.page.evaluate(() => {
            const time = performance.now();
            feed.forEach(clearTimeout);
            if (window.live) live.pause();
            return { time, frames: probe.frames, updateMs: probe.updateMs, updates: probe.updates,
                points: testChart.data.datasets.map(d => d.data.length), received,
                values: testChart.data.datasets.map(d => d.data.at(-1).y) };
        });
        const elapsedMs = final.time - initial.time;
        const taskMs = (after.metrics.find(m => m.name === 'TaskDuration').value -
            before.metrics.find(m => m.name === 'TaskDuration').value) * 1000;
        const intervals = final.frames.slice(1).map((time, i) => time - final.frames[i]).sort((a, b) => a - b);
        const round = value => Math.round(value * 10) / 10;
        const percentile = (values, p) => values.length ? round(values[Math.min(values.length - 1, Math.floor(values.length * p))]) : null;
        assert.ok(final.frames.length > 1, 'The workload must actually render.');
        assert.equal(final.received, Math.max(0, Math.ceil(((options.measureMs || 3000) - 500) / 1000)), 'Identical incoming workload.');
        assert.deepEqual(final.values, Array.from({ length: options.datasets || 4 }, (_, i) => 4 + i));
        await h.page.evaluate(() => { if (window.live) live.destroy(); testChart.destroy(); });
        assert.deepEqual(h.errors, []);
        return { mode, pointsPerDataset: options.points || 1000, datasets: options.datasets || 4,
            requestedFps: 30, elapsedMs: round(elapsedMs), taskMs: round(taskMs),
            mainThreadPercent: round(taskMs / elapsedMs * 100), frames: final.frames.length,
            achievedFps: round(final.frames.length * 1000 / elapsedMs), taskMsPerFrame: round(taskMs / final.frames.length),
            frameIntervalMedianMs: percentile(intervals, 0.5), frameIntervalP95Ms: percentile(intervals, 0.95),
            updates: final.updates, updateMedianMs: percentile(final.updateMs.sort((a, b) => a - b), 0.5),
            initialPoints: initial.points, finalPoints: final.points, receivedPerDataset: final.received - initial.received };
    } finally { await h.context.close(); }
}

// Run separately from timing benchmarks: sampling itself changes execution cost.
async function profile(browser, options = {}) {
    const h = await openChart(browser, 'own', { points: 5000, datasets: 4, ...options });
    try {
        await h.page.waitForTimeout(1000);
        const cdp = await h.context.newCDPSession(h.page);
        await cdp.send('Profiler.enable');
        await cdp.send('Profiler.start');
        await h.page.waitForTimeout(options.measureMs || 3000);
        const { profile: sample } = await cdp.send('Profiler.stop');
        const nodes = new Map(sample.nodes.map(node => [node.id, node]));
        const totals = new Map();
        sample.samples.forEach((id, index) => {
            const frame = nodes.get(id).callFrame;
            const key = `${frame.functionName || '(anonymous)'}:${frame.lineNumber + 1}:${frame.columnNumber + 1}`;
            totals.set(key, (totals.get(key) || 0) + sample.timeDeltas[index]);
        });
        assert.deepEqual(h.errors, []);
        return { sampledMs: (sample.endTime - sample.startTime) / 1000,
            topSelfTime: Array.from(totals, ([location, microseconds]) => ({ location, ms: Math.round(microseconds / 1000) }))
                .sort((a, b) => b.ms - a.ms).slice(0, 20) };
    } finally { await h.context.close(); }
}

async function compareRendering(browser, controllerSource) {
    const pages = [];
    try {
        for (const source of [controllerSource, undefined]) {
            pages.push(await openChart(browser, 'own', { clock: true, controllerSource: source }));
        }
        const stages = [];
        for (const stage of ['initial', 'append', 'prune', 'paused-gap', 'resume']) {
            const images = [];
            for (const h of pages) {
                await h.page.evaluate(stage => {
                    live.pause();
                    if (stage === 'append') pushSample();
                    if (stage === 'prune') {
                        testChart.data.datasets.forEach(d => {
                            d.data = [-90000, -80000, -70000, -1000, 0].map((age, i) => ({ x: Date.now() + age, y: i }));
                        });
                        testChart.update('none');
                        testChart.setActiveElements([{ datasetIndex: 0, index: 4 }]);
                        testChart.tooltip.setActiveElements([{ datasetIndex: 0, index: 4 }], { x: 850, y: 100 });
                        live.maintain();
                    }
                }, stage);
                if (stage === 'paused-gap') {
                    await h.page.clock.runFor(180000);
                    await h.page.evaluate(() => { pushSample(); live.maintain(); });
                }
                if (stage === 'resume') await h.page.evaluate(() => { live.resume(); live.pause(); });
                await h.page.evaluate(() => testChart.update('none'));
                if (h === pages[0]) {
                    // The old controller's stale tooltip is a known defect, not the
                    // expected image. Build a corrected reference using public APIs.
                    await h.page.evaluate(() => {
                        const active = testChart.tooltip.getActiveElements();
                        if (!active.length) return;
                        const position = { x: testChart.tooltip.caretX, y: testChart.tooltip.caretY };
                        testChart.tooltip.setActiveElements([], position);
                        testChart.tooltip.setActiveElements(active, position);
                        testChart.draw();
                    });
                }
                images.push(await h.page.locator('canvas').screenshot());
                assert.deepEqual(h.errors, []);
            }
            assert.ok(images[0].equals(images[1]), `Baseline/optimized canvas differs at ${stage}.`);
            stages.push(stage);
        }
        return { result: 'PASS', stages, scope: 'pixel-identical to baseline with explicitly refreshed reference tooltip; fixed times and labels enabled' };
    } finally { for (const h of pages) await h.context.close(); }
}

async function selectionRegression(browser) {
    const h = await openChart(browser, 'own', { clock: true });
    try {
        const cases = await h.page.evaluate(() => {
            const results = [];
            const check = (condition, message) => { if (!condition) throw new Error(message); };
            live.pause();
            for (const scenario of ['retained', 'reselected', 'cleared', 'expired', 'no-tooltip']) {
                testChart.setActiveElements([]);
                if (testChart.tooltip) testChart.tooltip.setActiveElements([], { x: 0, y: 0 });
                testChart.data.datasets.forEach(d => {
                    d.data = [-90000, -80000, -70000, -1000, 0].map((age, i) => ({ x: Date.now() + age, y: i }));
                });
                if (scenario === 'no-tooltip') testChart.options.plugins.tooltip = false;
                testChart.update('none');
                const index = scenario === 'expired' ? 0 : 4;
                const selected = [0, 1].map(datasetIndex => ({ datasetIndex, index }));
                testChart.setActiveElements(selected);
                if (testChart.tooltip) testChart.tooltip.setActiveElements(selected, { x: 850, y: 100 });
                const originalTooltip = testChart.tooltip;
                const before = { updates: probe.updates, draws: probe.draws };
                live.maintain();
                check(probe.updates === before.updates && probe.draws === before.draws, 'Maintenance must not render, including during pause.');
                if (scenario === 'reselected') testChart.tooltip.setActiveElements([{ datasetIndex: 1, index: 2 }], { x: 700, y: 100 });
                if (scenario === 'cleared') testChart.tooltip.setActiveElements([], { x: 0, y: 0 });
                testChart.update('none'); // Also cover an explicit caller update while paused.
                check(probe.updates === before.updates + 1 && probe.draws === before.draws + 1, 'Reconciliation must not add another update or draw.');
                if (scenario === 'no-tooltip') {
                    check(testChart.tooltip === originalTooltip && testChart.options.plugins.tooltip === false, 'Do not recreate or enable a disabled tooltip.');
                    testChart.options.plugins.tooltip = {};
                    testChart.update('none');
                    check(testChart.tooltip.dataPoints.every(point => point.raw.y === 4 && point.parsed.y === 4 && point.formattedValue === '4'),
                        'A re-enabled tooltip must not keep the stale cache.');
                } else if (scenario === 'cleared' || scenario === 'expired') {
                    check(testChart.tooltip.getActiveElements().length === 0, 'Do not resurrect a cleared/expired selection.');
                    check(testChart.tooltip.opacity === 0, 'Hide an empty tooltip.');
                } else {
                    const expected = scenario === 'reselected' ? [3] : [4, 4];
                    check(testChart.tooltip.dataPoints.length === expected.length, 'Preserve the latest selection count.');
                    testChart.tooltip.dataPoints.forEach((point, i) => {
                        check(point.raw.y === expected[i] && point.parsed.y === expected[i] && point.formattedValue === String(expected[i]), 'Raw, parsed and formatted values must agree.');
                        check(testChart.tooltip.body[i].lines[0] === `Series ${point.datasetIndex + 1}: ${expected[i]}`, 'The visible tooltip text must match.');
                    });
                }
                testChart.getActiveElements().forEach(element => {
                    check(element.element === testChart.getDatasetMeta(element.datasetIndex).data[element.index], 'Hover selection must reference the remapped element.');
                });
                results.push(scenario);
            }
            const foreignPlugins = testChart.config.plugins.filter(plugin => plugin.id !== 'jslive-realtime-selection');
            live.destroy();
            check(testChart.config.plugins.length === foreignPlugins.length, 'Remove only the owned local hook.');
            const replacement = new JSLiveRealtimeWindow(testChart, { duration: 60000 });
            replacement.start();
            replacement.start();
            check(testChart.config.plugins.length === foreignPlugins.length + 1, 'Restart has exactly one local hook.');
            replacement.destroy();
            check(testChart.config.plugins.every((plugin, i) => plugin === foreignPlugins[i]), 'Preserve unrelated local plugins.');
            testChart.destroy();
            return results;
        });
        assert.deepEqual(h.errors, []);
        return { result: 'PASS', cases, scope: 'line/bar tooltip text, public update while paused, no extra render, selection changes, disabled tooltip, hook cleanup/restart' };
    } finally { await h.context.close(); }
}

async function run(browser, { benchmark = false, capture = false, baselineSource } = {}) {
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
                assert.equal(await h.page.evaluate(() => testChart.tooltip.dataPoints[0].parsed.y), 4);
                assert.equal(await h.page.evaluate(() => testChart.tooltip.dataPoints[0].formattedValue), '4');
                assert.deepEqual(await h.page.evaluate(() => testChart.tooltip.body[0].lines), ['Series 1: 4']);
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
        // Alternate ordering, real clock, warm-up, two independent trials.
        // TaskDuration is main-thread time, not system-wide CPU usage.
        for (const points of [1000, 5000]) {
            for (let trial = 1; trial <= 2; trial++) {
                const modes = baselineSource ? ['plugin', 'baseline', 'own'] : ['plugin', 'own'];
                if (trial % 2 === 0) modes.reverse();
                for (const mode of modes) {
                    results.benchmarks.push({ ...await measure(browser, mode === 'baseline' ? 'own' : mode,
                        { points, controllerSource: mode === 'baseline' ? baselineSource : undefined }), mode, trial });
                }
            }
        }
    }
    results.selection = await selectionRegression(browser);
    if (baselineSource) results.rendering = await compareRendering(browser, baselineSource);
    return results;
}

module.exports = run;
module.exports.openChart = openChart;
module.exports.measure = measure;
module.exports.profile = profile;
module.exports.compareRendering = compareRendering;
module.exports.selectionRegression = selectionRegression;
if (require.main === module) {
    (async () => {
        const { chromium } = require('playwright');
        const browser = await chromium.launch({ headless: true,
            ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? { executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE } : {}) });
        try {
            if (process.argv.includes('--profile')) console.log(JSON.stringify(await profile(browser), null, 2));
            else console.log(JSON.stringify(await run(browser, { benchmark: true,
                baselineSource: process.env.JSLIVE_BASELINE_CONTROLLER ? fs.readFileSync(process.env.JSLIVE_BASELINE_CONTROLLER, 'utf8') : undefined
            }), null, 2));
        }
        finally { await browser.close(); }
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
