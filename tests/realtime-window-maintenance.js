// Optional focused benchmark. No Chart.js/browser cost is included here.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const RealtimeWindow = require('./prototypes/realtime-window.js');

function benchmark(baselineSource, iterations = 200) {
    const implementations = { own: RealtimeWindow };
    if (baselineSource) {
        const sandbox = { module: { exports: {} } };
        // Same JS realm as the current module; a separate VM context would add
        // cross-context intrinsic overhead to only one implementation.
        vm.compileFunction(baselineSource, ['module'])(sandbox.module);
        implementations.baseline = sandbox.module.exports;
    }
    const results = [];
    for (const scenario of ['unchanged', 'expired-prefix', 'paused-gap']) {
        const source = Array.from({ length: 20000 }, (_, i) => ({
            x: scenario === 'unchanged' ? 60000 + i * 3 : -60000 + i * 9, y: i, c: 2
        }));
        // Reference rule, deliberately independent of the optimized interval calculation.
        const boundary = min => {
            const index = source.findIndex(p => p.x >= min);
            return Math.max(0, (index < 0 ? source.length : index) - 2);
        };
        const liveStart = boundary(60000);
        const frozenStart = boundary(-30000);
        const expected = source.filter((p, i) => i >= liveStart ||
            (scenario === 'paused-gap' && i >= frozenStart && p.x <= 30000));
        for (let trial = 1; trial <= 3; trial++) {
            const entries = Object.entries(implementations);
            if (trial % 2 === 0) entries.reverse();
            for (const [mode, Controller] of entries) {
                const dataset = { data: source };
                const chart = { options: { scales: { x: { type: 'time' } } }, data: { datasets: [dataset] },
                    getActiveElements: () => [], setActiveElements() {} };
                const live = new Controller(chart, { duration: 60000 }, { now: () => 120000 });
                if (scenario === 'paused-gap') live.pausedAt = 30000;
                for (let i = 0; i < 20; i++) { dataset.data = source; live.maintain(); }
                const start = performance.now();
                for (let i = 0; i < iterations; i++) { dataset.data = source; live.maintain(); }
                const elapsedMs = performance.now() - start;
                assert.equal(dataset.data.length, expected.length);
                dataset.data.forEach((point, i) => assert.equal(point, expected[i], 'Preserve each point and its metadata.'));
                results.push({ scenario, mode, trial, inputPoints: source.length, retainedPoints: expected.length,
                    iterations, msPerCall: Math.round(elapsedMs / iterations * 1000) / 1000 });
            }
        }
    }
    return results;
}

module.exports = benchmark;
if (require.main === module) {
    console.log(JSON.stringify(benchmark(process.env.JSLIVE_BASELINE_CONTROLLER ?
        fs.readFileSync(process.env.JSLIVE_BASELINE_CONTROLLER, 'utf8') : undefined), null, 2));
}
