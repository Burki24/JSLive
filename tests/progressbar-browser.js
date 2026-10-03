// Optional isolated browser contract test; no Symcon connection or package installation.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const template = read('SymconJSLive/templates/Progressbar.html');
const functions = (template.slice(template.indexOf('    function Load(){'), template.indexOf('    function connect()'))
    + template.slice(template.indexOf('    function Update('), template.indexOf('    window.onload')))
    .replaceAll('{INSTANCE}', '12345').replaceAll('{PASSWORD}', 'synthetic');
assert.ok(functions.includes('function RGBAToHexA') && functions.includes('function LoadBarConfig'));
const base = {
    Variable: 67890, Type: 'stroke', shape_svg: false, shape_path: '', shape_preset: 'line',
    data_precision: 0.1, data_precisionCustom: 0, reverse: false, data_min: 0, data_max: 100,
    data_animationDuration: 0.1, data_animationTransitionIn: false,
    stroke_color_rgb: { R: 17, G: 34, B: 51 }, stroke_color_Alpha: 1,
    stroke_dir: 'normal', stroke_lincap: '', stroke_width: 3, stroke_trailColor: '#cccccc',
    stroke_trailWidth: 1, stroke_Dash1: 0, stroke_Dash2: 0,
    fill_color_rgb: { R: 68, G: 85, B: 102 }, fill_color_Alpha: 1, fill_dir: 'ltr',
    fill_backgroundExtrude: 0, fill_backgroundColor: '#ffffff', fill_backgroundFile: false,
    override_stroke: '', override_fill: '', overrideWidth: 0, overrideHeight: 0,
    style_fontPosition: 'center', style_fontSize: 14, style_fontDisplay: true,
    style_fontColor: '#111111', style_fontFamily: 'Arial', prefix: 'Value: ', suffix: ' units'
};
const scenarios = [
    { name: 'line', options: {} },
    { name: 'circle', options: { shape_preset: 'circle', stroke_dir: 'reverse' } },
    { name: 'fill', options: { Type: 'fill', shape_path: 'M0 0H100V20H0Z' } },
    { name: 'path', options: { shape_path: 'M0 10H100', stroke_Dash1: 3, stroke_Dash2: 6 } },
    { name: 'svg', options: { shape_svg: true } },
    { name: 'reverse', options: { reverse: true }, initialDisplay: 75,
        values: [75, 25, 0, 100], expected: [25, 75, 100, 0] },
    { name: 'reverse-offset', options: { reverse: true, data_min: 20, data_max: 120 }, initialDisplay: 115,
        values: [75, 25, 20, 120], expected: [65, 115, 120, 20] },
    { name: 'reverse-negative', options: { reverse: true, data_min: -100, data_max: 0 }, initial: -75, initialDisplay: -25,
        values: [-25, -75, -100, 0], expected: [-75, -25, 0, -100] }
];
async function check(browser, scenario, width, probeAnimation = false) {
    const context = await browser.newContext({ viewport: { width, height: 500 } });
    const page = await context.newPage();
    page.setDefaultTimeout(5000);
    if (probeAnimation) await page.clock.install();
    const errors = [];
    let images = 0;
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('dialog', async dialog => { errors.push(dialog.message()); await dialog.dismiss(); });
    const sourcePaths = [...template.matchAll(/(?:src|href)="(\/hook\/JSLive\/js\/[^"\s]+)"/g)].map(m => m[1]);
    await context.route('**/*', route => {
        const url = new URL(route.request().url());
        if (url.origin === 'http://jslive.test' && route.request().method() === 'GET') {
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body:
                template.slice(0, template.indexOf('<script>')).replace('{FONTS}', '').replace('{VIEWPORT}', '') + '</body></html>' });
            if (sourcePaths.includes(url.pathname)) return route.fulfill({
                contentType: url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript',
                body: read(url.pathname.replace('/hook/JSLive/', 'SymconJSLive/')) });
            if (url.pathname === '/hook/JSLive/getSVG') {
                images++;
                return route.fulfill({ contentType: 'image/svg+xml', body:
                    '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="20" viewBox="0 0 100 20"><rect width="100" height="20" fill="#4488cc"/></svg>' });
            }
        }
        errors.push('Unexpected request: ' + url.pathname);
        return route.abort();
    });
    try {
        await page.goto('http://jslive.test/');
        const initialRaw = scenario.initial ?? 25;
        const initialDisplay = scenario.initialDisplay ?? 25;
        await page.addScriptTag({ content: `var value = ${initialRaw}; var configuration = ${JSON.stringify({ ...base, ...scenario.options })};\n${functions}` });
        await page.evaluate(() => Load());
        if (scenario.name === 'svg') {
            await page.waitForFunction(() => bar.inited);
            if (probeAnimation) {
                await page.clock.runFor(20);
                await page.clock.fastForward(500);
            }
        }
        await page.waitForFunction(expected => document.querySelector('.ldBar-label')?.textContent === String(expected)
            && bar.transition.time.src === undefined, initialDisplay);
        assert.equal(await page.evaluate(() => value), initialRaw, 'Initialization must preserve the raw value.');
        const geometry = () => page.evaluate(() => [...document.querySelectorAll('#Bar svg path, #Bar svg rect')]
            .map(node => [node.getAttribute('stroke-dasharray'), node.getAttribute('width'), node.getAttribute('height') ]));
        const initial = await geometry();
        assert.ok(initial.length > 0);
        assert.deepEqual(await page.evaluate(() => {
            const label = document.querySelector('.ldBar-label');
            return [getComputedStyle(label, '::before').content, getComputedStyle(label, '::after').content];
        }), ['"Value: "', '" units"']);
        await page.evaluate(() => Update(99999, 75));
        assert.equal(await page.evaluate(() => bar.value), initialDisplay, 'Ignore unrelated variable updates.');
        const values = scenario.values || [75, 50.5, 0, 100];
        const expectedValues = scenario.expected || values;
        for (const [index, value] of values.entries()) {
            await page.evaluate(value => Update(67890, value), value);
            if (probeAnimation) {
                await page.clock.runFor(20);
                await page.clock.fastForward(500); // Delayed frame, e.g. a temporarily busy tab.
            }
            await page.waitForFunction(value => document.querySelector('.ldBar-label').textContent === String(value)
                && bar.transition.time.src === undefined, expectedValues[index]);
            assert.equal(await page.evaluate(() => value), value);
            const animatedGeometry = await geometry();
            await page.evaluate(value => bar.set(value, false), expectedValues[index]);
            assert.deepEqual(await geometry(), animatedGeometry, 'Animated and immediate target geometry must agree.');
        }
        assert.notDeepEqual(await geometry(), initial, 'Progress must change the SVG geometry.');
        assert.equal(images, scenario.name === 'svg' ? 1 : 0);
        assert.equal(await page.locator('#Bar > svg').count(), 1);
        assert.equal(await page.evaluate(() => !!document.getElementById('mask')), scenario.name === 'path');
        assert.deepEqual(errors, []);
        return { scenario: scenario.name, width, delayedFrames: probeAnimation, result: 'PASS' };
    } catch (error) {
        throw new Error(JSON.stringify({ scenario: scenario.name, width, errors, state: await page.evaluate(() => ({
            text: document.querySelector('.ldBar-label')?.textContent,
            value: typeof bar === 'undefined' ? null : bar.value,
            time: typeof bar === 'undefined' ? null : bar.transition.time
        })) }), { cause: error });
    } finally { await context.close(); }
}
async function run(browser, probeAnimation = false) {
    const results = [];
    if (probeAnimation) results.push(await check(browser, scenarios[0], 1024, true));
    else for (const delayed of [false, true]) for (const width of [1024, 390]) {
        for (const scenario of scenarios) results.push(await check(browser, scenario, width, delayed));
    }
    return { browser: browser.version(), results };
}
module.exports = run;
if (require.main === module) {
    (async () => {
        const { chromium } = require('playwright');
        const browser = await chromium.launch({ headless: true,
            ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? { executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE } : {}) });
        try { console.log(JSON.stringify(await run(browser, process.argv.includes('--probe-animation')), null, 2)); }
        finally { await browser.close(); }
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
