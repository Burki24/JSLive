// Optional isolated browser check; Playwright is supplied by the caller.
// Real bundles and template functions, synthetic configuration/HTTP only.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const templates = ['Radial', 'Linear', 'Linear(vertical)', 'Compass'];
const configuration = {
    min: 0, max: 1000, precision: 1, overrideWidth: 0, overrideHeight: 0,
    AutoConvertValue: true, AutoConvertHighlight: true, style_fontFamily: 'Arial',
    title_display: true, title_text: 'Gauge test', title_fontSize: 18,
    title_fontColor: '#111111', title_fontFamily: 'Arial',
    plate_unit: 'units', plate_unit_fontSize: 16, plate_unit_fontColor: '#111111',
    plate_unit_fontFamily: 'Arial', plate_colorPlate: '#ffffff', plate_colorPlateEnd: '#eeeeee',
    needle_display: true, needle_Type: 'arrow', needle_start: 0, needle_end: 80,
    needle_width: 2, needle_colorNeedle: '#cc0000', needle_colorNeedleEnd: '#990000',
    needle_colorShadowUp: 'rgba(0,0,0,0.05)', needle_colorShadowDown: 'rgba(0,0,0,0.5)',
    needle_CircleSize: 10, needle_CircleOuter: true, needle_colorNeedleCircleOuter: '#cccccc',
    needle_colorNeedleCircleOuterEnd: '#ffffff', needle_colorNeedleCircleInner: '#cccccc',
    needle_colorNeedleCircleInnerEnd: '#ffffff',
    valuebox_display: true, valuebox_separator: true, valuebox_fontSize: 14,
    valuebox_colorValueBoxBackground: '#ffffff', valuebox_fontColor: '#111111', valuebox_fontFamily: 'Arial',
    progressbar_display: true, progressbar_barWidth: 5, progressbar_barShadow: 1,
    progressbar_colorBar: '#eeeeee', progressbar_colorBarProgress: '#0066cc',
    linear_tickSide: 'both', linear_numberSide: 'both', linear_needleSide: 'both',
    radial_startAngle: 60, radial_ticksAngle: 270,
    ticks_colorMajorTick: '#111111', ticks_colorMinorTicks: '#111111', ticks_colorNumbers: '#111111',
    ticks_minorTicks: 5, ticks_strokeTicks: false, ticks_highlightsWidth: 5,
    ticks_fontSize: 12, ticks_exactTicks: false, ticks_fontFamily: 'Arial',
    animation_rule: 'linear', animation_duration: 40, animation_target: 'needle'
};
function functionsFrom(template, compass) {
    const start = template.indexOf('    function LoadGauge(');
    const connect = template.indexOf('    function connect(', start);
    const update = template.indexOf(compass ? '    function UpdateGauge(' : '    function convertValue(', connect);
    const pull = template.indexOf('    async function PullNewData(', update);
    assert.ok(start >= 0 && connect > start && update > connect && pull > update);
    return (template.slice(start, connect) + template.slice(update, pull))
        .replaceAll('{INSTANCE}', '12345').replaceAll('{PASSWORD}', 'synthetic');
}
async function check(browser, name, width) {
    const context = await browser.newContext({ viewport: { width, height: 600 } });
    const page = await context.newPage();
    page.setDefaultTimeout(5000);
    const errors = [];
    let requests = 0;
    const compass = name === 'Compass';
    const config = { ...configuration, ...(compass ? { max: 360 } : {}) };
    const template = read(`SymconJSLive/templates/CanvasGauges-${name}.html`);
    const sources = [...template.matchAll(/<script src="\/hook\/JSLive\/(js\/[^"\s]+)"><\/script>/g)].map(m => m[1]);
    assert.equal(sources.length, 3);
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('dialog', async dialog => { errors.push(dialog.message()); await dialog.dismiss(); });
    await context.route('**/*', route => {
        const url = new URL(route.request().url());
        if (url.origin === 'http://jslive.test' && route.request().method() === 'GET') {
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: '<!doctype html><canvas id="gauge"></canvas>' });
            if (url.pathname === '/hook/JSLive/getData' && url.searchParams.get('Instance') === '12345') {
                requests++;
                return route.fulfill({ json: { Variable: 67890, Value: compass ? 90 : 250 } });
            }
        }
        errors.push('Unexpected request: ' + url.pathname);
        return route.abort();
    });
    try {
        await page.goto('http://jslive.test/');
        for (const source of sources) await page.addScriptTag({ content: read('SymconJSLive/' + source) });
        await page.addScriptTag({ content: `let gauge;
            let configuration = ${JSON.stringify(config)};
            let config_ticks = [0, 100, 500, 1000];
            let config_highlights = [{from:100,to:500,color:'rgba(0,128,0,0.3)'}];
            let curValue = 0, separator_char = '', fontLoaded = false;
            ${functionsFrom(template, compass)}` });
        await page.evaluate(compass => {
            UpdateGauge(67890, 0);
            LoadGauge();
            window.animationEnds = 0;
            // Compass is constructed immediately, then animates its Ajax value.
            // The other templates construct the gauge with the fetched value.
            if (compass) gauge.on('animationEnd', () => animationEnds++);
        }, compass);
        await page.waitForFunction(value => typeof gauge !== 'undefined' && Math.abs(gauge.value - value) < 0.0001, compass ? 90 : 458.3333333333333);
        await page.waitForFunction(compass => (!compass || animationEnds === 1)
            && Math.abs(gauge.options.value - gauge.value) < 0.0001, compass);
        await page.evaluate(compass => {
            window.animationEnds = 0;
            if (!compass) gauge.on('animationEnd', () => animationEnds++);
        }, compass);
        const state = await page.evaluate(() => ({ version: document.gauges.version,
            type: gauge instanceof LinearGauge ? 'linear' : 'radial',
            text: gauge.options.valueText, highlights: gauge.options.highlights,
            width: gauge.options.width, height: gauge.options.height,
            image: document.getElementById('gauge').toDataURL() }));
        assert.equal(state.version, '2.1.7');
        assert.equal(state.type, name.startsWith('Linear') ? 'linear' : 'radial');
        assert.ok(state.width > 0 && state.height > 0);
        if (!compass) {
            assert.equal(state.text, '250,0');
            assert.ok(Math.abs(state.highlights[0].from - 1000 / 3) < 0.0001);
            assert.ok(Math.abs(state.highlights[0].to - 2000 / 3) < 0.0001);
        }
        let finishedAnimations = 0;
        for (const [input, expected, text] of compass
            ? [[-1, 0], [180, 180], [400, 360]]
            : [[-50, 0, '-50,0'], [500, 2000 / 3, '500,0'], [1250.5, 1000, '1.250,5']]) {
            await page.evaluate(value => UpdateGauge(67890, value), input);
            finishedAnimations++;
            // Floating-point closeness can occur one frame before completion.
            // This matrix tests sequential animations, not interrupted ones.
            await page.waitForFunction(({ value, ends }) => animationEnds === ends
                && Math.abs(gauge.value - value) < 0.0001
                && Math.abs(gauge.options.value - value) < 0.0001,
            { value: expected, ends: finishedAnimations });
            if (!compass) assert.equal(await page.evaluate(() => gauge.options.valueText), text);
        }
        assert.notEqual(await page.evaluate(() => document.getElementById('gauge').toDataURL()), state.image, 'Value changes must change the rendered canvas.');
        assert.equal(await page.evaluate(() => animationEnds), 3, 'All value animations must finish.');
        await page.evaluate(async () => { await document.fonts.ready; gauge.update(); });
        assert.equal(await page.evaluate(() => document.gauges.length), 1);
        await page.evaluate(() => gauge.destroy());
        assert.equal(await page.evaluate(() => document.gauges.length), 0);
        assert.equal(requests, 1);
        assert.deepEqual(errors, []);
        return { template: name, width, result: 'PASS' };
    } catch (error) {
        throw new Error(JSON.stringify({ name, width, errors, state: await page.evaluate(() =>
            typeof gauge === 'undefined' ? null : { value: gauge.value, renderedValue: gauge.options.value,
                text: gauge.options.valueText, animationEnds: window.animationEnds }) }), { cause: error });
    } finally { await context.close(); }
}
async function run(browser) {
    const results = [];
    for (const width of [1024, 390]) for (const name of templates) results.push(await check(browser, name, width));
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
