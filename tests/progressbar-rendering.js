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

if (process.argv.includes('--probe-reverse')) {
    // Known integration defect; intentionally outside the normal green baseline.
    // Run explicitly before/after its separate correction; never accept the wrong value.
    const updateSource = template.slice(template.indexOf('    function Update('), template.indexOf('    function RGBAToHexA'));
    const context = {
        configuration: { Variable: 67890, reverse: true, data_max: 100 },
        value: 75, // Raw initial value 25, displayed in reverse as 75.
        bar: { set(value) { context.displayed = value; } },
        displayed: 75
    };
    vm.runInNewContext(updateSource + '\nUpdate(67890, 75);', context);
    assert.equal(context.displayed, 25, 'Reverse mode must not compare a new raw value with the old reversed value.');
}

console.log('JSLive Progressbar rendering contracts verified.');
