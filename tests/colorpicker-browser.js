// Optional real-browser test. Local assets and synthetic endpoints only; no screenshots.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
async function run(browser) {
    const context = await browser.newContext({viewport: {width: 500, height: 500}});
    const page = await context.newPage();
    page.setDefaultTimeout(5000);
    const errors = [], writes = [];
    let readCount = 0, rejectWrite = false;
    const data = [
        {Variable: 101, Mode: 'xy', Value: '{"x":0.64,"y":0.33}', ColorValue: 0xFF0000, Error: ''},
        {Variable: 102, Mode: 'xy', Value: '{"x":0.3,"y":0.6}', ColorValue: 0x00FF00, Error: ''},
        {Variable: 103, Mode: 'color', Value: 255}
    ];
    const configuration = {DataUpdateRate: 5, manWidth: 0, layout_Direction: 'vertical',
        style_handleRadius: 8, style_borderWidth: 2, style_borderColor: '#000',
        wheel_Lightness: true, wheel_Angle: 0, wheel_Direction: 'anticlockwise', overrideWidth: 0, overrideHeight: 0};
    const html = read('SymconJSLive/templates/ColorPicker.html').replace('{GLOBAL}', JSON.stringify({LocalAddress: 'synthetic', RemoteAddress: 'synthetic'}))
        .replace('{VARIABELS}', JSON.stringify(data)).replace('{LAYOUT}', '[]').replace('{CONFIG}', JSON.stringify(configuration))
        .replace('{VIEWPORT}', '<meta name="viewport" content="width=device-width, initial-scale=1">')
        .replaceAll('{INSTANCE}', '12345').replaceAll('{PASSWORD}', 'synthetic');
    page.on('pageerror', error => errors.push(error.message));
    await page.addInitScript(() => {
        window.WebSocket = class {constructor() {window.testSocket = this;} };
    });
    const assets = new Map([...html.matchAll(/src="(\/hook\/JSLive\/js\/[^"\s]+)"/g)]
        .map(match => [match[1], read('SymconJSLive/' + match[1].slice('/hook/JSLive/'.length))]));
    await context.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (assets.has(url.pathname)) return route.fulfill({contentType: 'application/javascript', body: assets.get(url.pathname)});
        if (url.pathname === '/') return route.fulfill({contentType: 'text/html', body: html});
        if (url.pathname.endsWith('/getData')) {
            readCount++;
            return route.fulfill({contentType: 'application/json', body: JSON.stringify(data)});
        }
        if (url.pathname.endsWith('/setData')) {
            const id = Number(url.searchParams.get('var')), value = Number(url.searchParams.get('val'));
            writes.push({id, value});
            if (rejectWrite) return route.fulfill({body: 'XY COLOR UPDATE FAILED!'});
            const row = data.find(part => part.Variable === id);
            if (row.Mode === 'xy') { row.ColorValue = value; row.Value = '{"x":0.3127,"y":0.329}'; }
            else row.Value = value;
            return route.fulfill({body: 'OK'});
        }
        return route.abort();
    });
    try {
        await page.goto('http://jslive.test/');
        await page.waitForFunction(() => window.testSocket && colorPicker.colors.length === 3);
        assert.deepEqual(await page.evaluate(() => colorPicker.colors.map(color => color.hexString.toLowerCase())), ['#ff0000', '#00ff00', '#0000ff']);
        assert.equal(writes.length, 0);
        // Genuine pointer input on the rendered iro wheel, with the second lamp selected.
        await page.evaluate(() => colorPicker.setActiveColor(1));
        const wheel = await page.locator('.IroWheel').boundingBox();
        assert.ok(wheel && wheel.width > 0);
        await page.mouse.click(wheel.x + wheel.width * 0.7, wheel.y + wheel.height * 0.35);
        await page.waitForFunction(() => !running && refreshPromise === null);
        assert.ok(writes.length > 0 && writes.every(write => write.id === 102));
        assert.ok(writes.every(write => Number.isInteger(write.value) && write.value >= 0 && write.value <= 0xFFFFFF));
        assert.equal(await page.evaluate(() => colorPicker.color.index), 1, 'Readback must preserve the selected lamp');
        const before = writes.length;
        data[0].Value = '{"x":0.15,"y":0.06}'; data[0].ColorValue = 255;
        await page.evaluate(raw => window.testSocket.onmessage({data: JSON.stringify({Message: 10603, SenderID: 101, Data: [raw]})}), data[0].Value);
        await page.waitForFunction(() => colorPicker.colors[0].hexString.toLowerCase() === '#0000ff');
        assert.equal(writes.length, before, 'WebSocket feedback cannot trigger a user action');
        data[1].ColorValue = 0xFFFFFF; data[1].Value = '{"x":0.3127,"y":0.329}';
        await page.evaluate(() => RefreshColorData());
        assert.equal(await page.evaluate(() => colorPicker.colors[1].hexString.toLowerCase()), '#ffffff');
        assert.equal(writes.length, before, 'Pull updates cannot trigger a user action');
        assert.equal(await page.evaluate(() => colorPicker.color.index), 1, 'External updates must preserve the selected lamp');
        rejectWrite = true;
        await page.mouse.click(wheel.x + wheel.width * 0.55, wheel.y + wheel.height * 0.25);
        await page.waitForFunction(() => !running && refreshPromise === null && document.getElementById('picker-status').textContent.includes('Color update failed'));
        data[0].ColorValue = null; data[0].Error = 'Invalid xy color';
        await page.evaluate(() => RefreshColorData());
        assert.match(await page.locator('#picker-status').textContent(), /Invalid xy color/);
        assert.equal(await page.evaluate(() => colorPicker.colors.length), 3);
        await page.setViewportSize({width: 280, height: 320});
        await page.waitForFunction(() => document.querySelector('.IroWheel').getBoundingClientRect().width <= 260);
        assert.deepEqual(errors, []);
        return {browser: browser.version(), handles: 3, pointerWrites: writes.length, normalizedReads: readCount,
            websocket: 'pass', pull: 'pass', noEcho: 'pass', writeFailure: 'pass', resize: 'pass'};
    } finally { await context.close(); }
}
module.exports = run;
if (require.main === module) {
    (async () => {
        const {chromium} = require('playwright');
        const browser = await chromium.launch({headless: true,
            ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? {executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE} : {})});
        try { console.log(JSON.stringify(await run(browser), null, 2)); }
        finally { await browser.close(); }
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
