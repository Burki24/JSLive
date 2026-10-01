const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { spawnSync } = require('node:child_process');

// Separate processes give the real Moment/Date implementation a fixed zone,
// including on Windows. No packages, canvas mocks or live Symcon data needed.
if (process.argv[2] !== '--timezone-case') {
    for (const timezone of ['UTC', 'Europe/Berlin']) {
        const result = spawnSync(process.execPath, [__filename, '--timezone-case'], {
            env: { ...process.env, TZ: timezone },
            stdio: 'inherit'
        });
        if (result.error) throw result.error;
        assert.equal(result.status, 0, `Moment adapter checks must pass in ${timezone}.`);
    }
    console.log('JSLive Moment adapter contracts verified in UTC and Europe/Berlin.');
} else {
    const root = path.join(__dirname, '..', 'SymconJSLive');
    for (const [asset, version] of [
        ['moment/2.27.0/Moment.js', '2.27.0'],
        ['moment/2.31.0/moment.min.js', '2.31.0']
    ]) {
        const context = vm.createContext({ Date, console });
        for (const file of ['chartjs/4.5.1/chart.umd.min.js', asset, 'chartjs/plugins/chartjs-adapter-moment.js']) {
            vm.runInContext(fs.readFileSync(path.join(root, 'js', file), 'utf8'), context, { filename: file });
        }
        assert.equal(context.moment.version, version);
        assert.deepEqual(Array.from(context.moment.locales()), ['en'], 'Do not silently change the locale bundle.');
        const adapter = new context.Chart._adapters._date({});
        assert.equal(adapter._id, 'moment');
        assert.equal(adapter.formats().day, 'MMM D');
        assert.equal(adapter.parse(0), 0, 'Epoch zero must not be treated as empty.');
        assert.equal(adapter.parse(null), null);
        assert.equal(adapter.parse('2024-02-30', 'YYYY-MM-DD'), null, 'Reject impossible dates.');
        assert.equal(adapter.parse('2024-02-29T12:34:56+01:00'), Date.UTC(2024, 1, 29, 11, 34, 56));
        const leapDay = new Date(2024, 1, 29, 13, 45, 12).getTime();
        assert.equal(adapter.parse('29.02.2024 13:45:12', 'DD.MM.YYYY HH:mm:ss'), leapDay);
        assert.equal(adapter.format(leapDay, 'DD.MM.YYYY HH:mm:ss'), '29.02.2024 13:45:12');
        assert.equal(adapter.add(new Date(2024, 0, 31).getTime(), 1, 'month'), new Date(2024, 1, 29).getTime());
        assert.equal(adapter.add(new Date(2024, 1, 29).getTime(), 1, 'year'), new Date(2025, 1, 28).getTime());
        assert.equal(adapter.diff(new Date(2024, 1, 29).getTime(), new Date(2024, 1, 27).getTime(), 'day'), 2);
        assert.equal(adapter.startOf(leapDay, 'month'), new Date(2024, 1, 1).getTime());
        assert.equal(adapter.endOf(leapDay, 'month'), new Date(2024, 2, 1).getTime() - 1);
        assert.equal(adapter.startOf(leapDay, 'isoWeek', 1), new Date(2024, 1, 26).getTime());
        assert.equal(adapter.startOf(leapDay, 'quarter'), new Date(2024, 0, 1).getTime());
        assert.equal(adapter.endOf(leapDay, 'year'), new Date(2025, 0, 1).getTime() - 1);
        for (const [month, day, berlinHours] of [[2, 31, 23], [9, 27, 25]]) {
            const start = new Date(2024, month, day).getTime();
            const next = adapter.add(start, 1, 'day');
            assert.equal(next, new Date(2024, month, day + 1).getTime(), 'Calendar days must retain local midnight.');
            assert.equal((next - start) / 3600000, process.env.TZ === 'Europe/Berlin' ? berlinHours : 24);
        }
        console.log(`Moment ${version}: adapter behavior passed in ${process.env.TZ}.`);
    }
    for (const template of ['Chart.html', 'Doughnut-PIE.html', 'RadarChart.html']) {
        const html = fs.readFileSync(path.join(root, 'templates', template), 'utf8');
        const refs = Array.from(html.matchAll(/<script\b[^>]*\bsrc="(\/hook\/JSLive\/js\/moment\/[^\"]+)"/gi), m => m[1]);
        assert.deepEqual(refs, ['/hook/JSLive/js/moment/2.31.0/moment.min.js'], `${template} must load Moment 2.31.0 exactly once.`);
        assert.ok(html.indexOf(refs[0]) < html.indexOf('/hook/JSLive/js/chartjs/plugins/chartjs-adapter-moment.js'), 'Moment must load before its adapter.');
    }
}
