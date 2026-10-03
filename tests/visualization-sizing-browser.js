// Optional Playwright check: local assets, synthetic values, no Symcon access.
// Resize an iframe while the outer browser stays unchanged (IPSView-like embedding).
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8').replace(/\r\n/g, '\n');
function fn(source, name) {
    const start = source.indexOf(`    function ${name}(`);
    const end = source.indexOf('\n    }', start);
    assert.ok(start >= 0 && end > start, name);
    return source.slice(start, end + 6);
}
const sizes = [[1024, 600], [480, 220], [940, 390], [320, 600], [220, 150], [1024, 600]];
async function check(browser, name, options = {}) {
    const context = await browser.newContext({ viewport: { width: 1300, height: 900 } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => { errors.push(dialog.message()); await dialog.dismiss(); });
    const source = read(`SymconJSLive/templates/${name}.html`);
    const inline = source.search(/<script(?: type="[^"]+")?>/);
    assert.ok(inline > 0);
    let html = source.slice(0, inline).replace(/\{(?:FONTS|VIEWPORT)\}/g, '')
        .replace(/<script src="[^"]+"><\/script>/g, '');
    const chart = ['Chart', 'Doughnut-PIE', 'RadarChart'].includes(name);
    const picker = name === 'ColorPicker';
    const assetPaths = ['/hook/JSLive/js/util.js'];
    if (chart) assetPaths.push(source.match(/src="([^"\n]+chart\.umd[^"\n]+)"/i)?.[1]
        || source.match(/src="([^"\n]+chart\.umd\.js)"/i)?.[1]);
    if (picker) assetPaths.push('/hook/JSLive/js/iro/5.5.0/iro.js');
    assert.ok(assetPaths.every(Boolean), `${name}: assets`);
    html += assetPaths.map(url => `<script src="${url}"></script>`).join('') + '</body></html>';
    const configuration = { overrideWidth: 0, overrideHeight: 0, Ratio: options.ratio || 0,
        manWidth: 0, layout_Direction: options.direction || 'vertical', style_handleRadius: 8,
        style_borderWidth: 1, style_borderColor: '#333333', style_fontFamily: 'Arial', style_fontSize: 14,
        style_backgroundColor: '#ffffff', style_fontColor: '#111111', style_borderRadius: '4px',
        style_highlightColor1: '#222222', style_highlightColor2: '#555555',
        style_highlightColor3: '#888888', style_highlightColor4: '#aaaaaa',
        wheel_Lightness: true, wheel_Angle: 0, wheel_Direction: 'anticlockwise' };
    await context.route('**/*', route => {
        const url = new URL(route.request().url());
        if (url.origin === 'http://jslive.test' && route.request().method() === 'GET') {
            if (url.pathname === '/frame') return route.fulfill({ contentType: 'text/html', body: html });
            if (url.pathname.startsWith('/hook/JSLive/js/') && !url.pathname.includes('..')) return route.fulfill({
                contentType: url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript',
                body: read(url.pathname.replace('/hook/JSLive/', 'SymconJSLive/')) });
        }
        errors.push('Unexpected request: ' + url.pathname);
        return route.abort();
    });
    try {
        await page.setContent('<iframe style="border:0;display:none;width:1024px;height:600px" src="http://jslive.test/frame"></iframe>');
        const frame = page.frames().find(frame => frame !== page.mainFrame());
        await frame.waitForLoadState();
        await frame.evaluate(config => { window.configuration = config; }, configuration);
        let target;
        if (chart) {
            const start = source.indexOf('    JSLiveObserveSize(ResizeChart);');
            const events = name === 'Chart' ? source.slice(start, source.indexOf('    function ResizeChart', start))
                : source.slice(source.indexOf("    window.addEventListener('resize', ResizeChart);"), source.indexOf('    function ResizeChart'));
            await frame.addScriptTag({ content: `let myChart; ${fn(source, 'ResizeChart')}\n${events}` });
            await frame.evaluate(name => {
                ResizeChart();
                myChart = new Chart(document.getElementById('myChart'), {
                    type: name === 'RadarChart' ? 'radar' : name === 'Doughnut-PIE' ? 'doughnut' : 'line',
                    data: { labels: ['A', 'B', 'C'], datasets: [{ data: [20, 40, 60] }] },
                    options: { responsive: true, maintainAspectRatio: name !== 'Chart', animation: false,
                        aspectRatio: configuration.Ratio || 1 }
                });
                window.original = myChart;
            }, name);
            target = '#myChart';
        } else if (picker) {
            const layout = options.multi ? [{ Layout: 'Wheel' }, { Layout: 'Box' },
                { Layout: 'Slider', sliderShape: 'circle', sliderType: 'hue' },
                { Layout: 'Slider', sliderSize: 30, sliderType: 'value' }] : [];
            await frame.addScriptTag({ content: `let config_layout = ${JSON.stringify(layout)};
                let config_variabels = [{Variable:123,Mode:'color',Value:16711680}];
                ${['LoadPicker', 'PickerWidth', 'ResizePicker', 'GenerateLayoutData', 'GenerateColorData'].map(name => fn(source, name)).join('\n')}
                let colorPicker = new iro.ColorPicker('#picker', LoadPicker());
                window.original = colorPicker; window.changes = 0;
                colorPicker.on('color:change', () => changes++);
                JSLiveObserveSize(ResizePicker);` });
            target = '#picker .IroColorPicker';
        } else {
            const load = fn(source, 'Load');
            // Real input initialization and icon formatting; no transport startup.
            await frame.addScriptTag({ content: `let value = 43200; function updateValue() {}
                ${load}\n${source.includes('    function unixTimeToString(') ? fn(source, 'unixTimeToString') : ''}
                ${source.includes('    function UpdateIcon(') ? fn(source, 'UpdateIcon') : ''}
                Load();` });
            target = name === 'TimePicker2' ? '#divbox' : name === 'Textfield2' ? '#box' : 'input';
        }
        await page.locator('iframe').evaluate(element => { element.style.display = 'block'; });
        for (const [width, height] of sizes) {
            await page.locator('iframe').evaluate((element, size) => {
                element.style.width = size[0] + 'px'; element.style.height = size[1] + 'px';
            }, [width, height]);
            await frame.waitForFunction(({ target, width, height, chart, picker, options }) => {
                const box = document.querySelector(target).getBoundingClientRect();
                if (chart) {
                    const container = document.getElementById('chart-container').getBoundingClientRect();
                    const expectedHeight = options.ratio ? Math.min(height - 20, (width - 20) / options.ratio) : height - 20;
                    const expectedWidth = options.ratio ? expectedHeight * options.ratio : width - 20;
                    if (Math.abs(container.width - expectedWidth) > 0.5 || Math.abs(container.height - expectedHeight) > 0.5) return false;
                }
                if (picker) {
                    const primary = options.direction === 'horizontal' ? width - 20 : height - 20;
                    const cross = options.direction === 'horizontal' ? height - 20 : width - 20;
                    const expected = Math.floor(Math.min(cross, (primary - (options.multi ? 66 : 52)) / (options.multi ? 3 : 1)));
                    if (Math.abs(document.querySelector('#picker .IroWheel').getBoundingClientRect().width - expected) > 0.5) return false;
                }
                return innerWidth === width && innerHeight === height && box.width > 0 && box.height > 0
                    && box.left >= 0 && box.top >= 0 && box.right <= width + 0.5 && box.bottom <= height + 0.5
                    && document.documentElement.scrollWidth <= width && document.documentElement.scrollHeight <= height;
            }, { target, width, height, chart, picker, options }, { timeout: 5000 }).catch(async error => {
                throw new Error(`${name} ${JSON.stringify(options)} at ${width}x${height}: ` + JSON.stringify(await frame.evaluate(target => ({
                    rect: document.querySelector(target).getBoundingClientRect().toJSON(),
                    scroll: [document.documentElement.scrollWidth, document.documentElement.scrollHeight]
                }), target).catch(() => errors)), { cause: error });
            });
            if (chart) assert.equal(await frame.evaluate(() => original === myChart && myChart.data.datasets[0].data[0] === 20), true);
            if (picker) assert.deepEqual(await frame.evaluate(() => [original === colorPicker, changes, colorPicker.color.hexString]), [true, 0, '#ff0000']);
        }
        if (picker) {
            await frame.evaluate(() => { configuration.manWidth = 250; window.beforeWidth = document.querySelector('#picker .IroWheel').getBoundingClientRect().width; });
            await frame.evaluate(() => ResizePicker());
            assert.equal(await frame.evaluate(() => document.querySelector('#picker .IroWheel').getBoundingClientRect().width === beforeWidth), true);
        }
        assert.deepEqual(errors, []);
        return { name, ...options, result: 'PASS' };
    } finally { await context.close(); }
}
async function run(browser) {
    const results = [];
    for (const name of ['Chart', 'Doughnut-PIE', 'RadarChart', 'TimePicker1', 'TimePicker2', 'TimePicker3',
        'DatePicker1', 'DateTimePicker1', 'Textfield1', 'Textfield2']) {
        results.push(await check(browser, name));
        if (['Doughnut-PIE', 'RadarChart'].includes(name)) results.push(await check(browser, name, { ratio: 2 }));
    }
    for (const direction of ['vertical', 'horizontal']) for (const multi of [false, true]) {
        results.push(await check(browser, 'ColorPicker', { direction, multi }));
    }
    return { browser: browser.version(), results };
}
module.exports = run;
if (require.main === module) (async () => {
    const { chromium } = require('playwright');
    const browser = await chromium.launch({ headless: true,
        ...(process.env.JSLIVE_BROWSER_EXECUTABLE ? { executablePath: process.env.JSLIVE_BROWSER_EXECUTABLE } : {}) });
    try { console.log(JSON.stringify(await run(browser), null, 2)); }
    finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
