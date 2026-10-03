const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const templatePath = path.join(__dirname, '..', 'SymconJSLive', 'templates', 'Progressbar.html');
const template = fs.readFileSync(templatePath, 'utf8');
const functionMatch = template.match(/function LoadBarConfig\(\)\{[\s\S]*?\n    \}\n\n\n    function connect\(\)/);

assert.ok(functionMatch, 'The Progressbar template must contain LoadBarConfig().');

const loadBarConfigSource = functionMatch[0].replace(/\n\n\n    function connect\(\)$/, '');
const baseConfiguration = {
    Type: 'stroke',
    shape_svg: false,
    shape_path: '',
    shape_preset: 'line',
    data_precision: 0,
    data_precisionCustom: 0,
    reverse: false,
    data_min: 0,
    data_max: 100,
    data_animationDuration: 500,
    data_animationTransitionIn: 0.5,
    stroke_color_rgb: { R: 17, G: 34, B: 51 },
    stroke_color_Alpha: 1,
    stroke_dir: 'normal',
    stroke_lincap: '',
    stroke_width: 3,
    stroke_trailColor: '#cccccc',
    stroke_trailWidth: 1,
    fill_color_rgb: { R: 68, G: 85, B: 102 },
    fill_color_Alpha: 1,
    fill_dir: 'ltr',
    fill_backgroundExtrude: 0,
    fill_backgroundColor: '#ffffff',
    fill_backgroundFile: false,
    override_stroke: '',
    override_fill: ''
};

function renderProgressbarConfig(overrides) {
    const context = {
        configuration: { ...baseConfiguration, ...overrides },
        value: 25,
        RGBAToHexA: (red, green, blue, alpha) => `rgba(${red}, ${green}, ${blue}, ${alpha})`
    };

    vm.runInNewContext(`${loadBarConfigSource}\nresult = LoadBarConfig();`, context);

    return context.result;
}

const customSvgStroke = renderProgressbarConfig({ Type: 'stroke', shape_svg: true });
assert.equal(customSvgStroke.type, 'fill', 'A custom SVG must use the supported fill rendering mode.');
assert.ok(customSvgStroke.img, 'A custom SVG must remain the rendered Progressbar image.');
assert.equal(customSvgStroke.preset, undefined, 'A custom SVG must not be replaced by a preset.');

const customSvgFill = renderProgressbarConfig({ Type: 'fill', shape_svg: true });
assert.equal(customSvgFill.type, 'fill', 'An explicitly configured SVG fill must remain unchanged.');

const presetStroke = renderProgressbarConfig({ Type: 'stroke', shape_svg: false });
assert.equal(presetStroke.type, 'stroke', 'A preset stroke must remain a stroke.');
assert.equal(presetStroke.preset, 'line', 'A preset stroke must retain its configured shape.');

const pathStroke = renderProgressbarConfig({ Type: 'stroke', shape_path: 'M0 0 L100 0' });
assert.equal(pathStroke.type, 'stroke', 'A custom path stroke must remain a stroke.');
assert.equal(pathStroke.path, 'M0 0 L100 0', 'A custom path must remain unchanged.');

const updateSource = template.slice(template.indexOf('    function Update('), template.indexOf('    function RGBAToHexA'));
assert.ok(updateSource.includes('function Update('));
for (const [reverse, min, max, initial, next, firstDisplay, nextDisplay] of [
    [true, 0, 100, 25, 75, 75, 25],
    [true, 20, 120, 25, 75, 115, 65],
    [true, -100, 0, -75, -25, -25, -75],
    [true, -50, 100, 0, 50, 50, 0],
    [true, 0, 100, 25.5, 74.5, 74.5, 25.5],
    [false, 20, 120, 25, 75, 25, 75]
]) {
    const calls = [];
    const context = {
        configuration: { ...baseConfiguration, Variable: 67890, reverse, data_min: min, data_max: max },
        value: initial,
        RGBAToHexA: () => '#112233ff',
        bar: { set(value) { calls.push(value); } }
    };
    vm.createContext(context);
    vm.runInContext(loadBarConfigSource + '\n' + updateSource, context);
    assert.equal(vm.runInContext('LoadBarConfig().value', context), firstDisplay);
    assert.equal(context.value, initial, 'Loading the config must preserve the raw value.');
    assert.equal(vm.runInContext('LoadBarConfig().value', context), firstDisplay, 'Repeated config generation must be idempotent.');
    vm.runInContext(`Update(99999, ${next}); Update(67890, ${initial});`, context);
    assert.deepEqual(calls, [], 'Ignore unrelated IDs and unchanged raw values.');
    vm.runInContext(`Update(67890, ${next}); Update(67890, ${next});`, context);
    assert.deepEqual(calls, [nextDisplay]);
    assert.equal(context.value, next);
    vm.runInContext(`Update(67890, ${initial});`, context);
    assert.deepEqual(calls, [nextDisplay, firstDisplay], 'Reverse updates must work in both directions.');
}

console.log('JSLive Progressbar rendering contracts verified.');
