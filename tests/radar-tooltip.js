const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const templatePath = path.join(__dirname, '..', 'SymconJSLive', 'templates', 'RadarChart.html');
const template = fs.readFileSync(templatePath, 'utf8').replace(/\r\n/g, '\n');

function extractFunction(name, nextName) {
    const start = template.indexOf(`    function ${name}(`);
    const end = template.indexOf(`    function ${nextName}(`, start);
    assert.ok(start >= 0 && end > start, `The Radar template must contain ${name}().`);
    return template.slice(start, end);
}

const context = {
    Chart: { register() {} },
    ChartDataLabels: {},
    configuration: { Ratio: 1 },
    config_labels: ['North', 'East', 'South'],
    config_dataset: [],
    config_title: {},
    config_legend: {},
    config_tooltips: {},
    alert(message) { throw new Error(message); }
};

vm.runInNewContext(
    extractFunction('UpdateTooltipLabel', 'UpdateLastValue') + '\n'
        + extractFunction('updateChartconfig', 'connect') + '\n'
        + 'result = updateChartconfig();',
    context,
    { filename: templatePath }
);

const labelCallback = context.result.options.plugins.tooltip.callbacks.label;
assert.equal(labelCallback, context.UpdateTooltipLabel, 'The standard Radar config must use the tested callback.');

// Chart.js supplies one TooltipItem, including dataset and formattedValue:
// https://www.chartjs.org/docs/latest/configuration/tooltip.html#tooltip-item-context
// Radar data is scalar (parsed.r), not the Cartesian {x, y} data used by Chart.
function tooltipItem(label, values, dataIndex, formattedValue) {
    const dataset = Object.freeze({ label, data: Object.freeze(values) });
    return Object.freeze({
        dataset,
        datasetIndex: 1,
        dataIndex,
        raw: values[dataIndex],
        parsed: Object.freeze({ r: Number(values[dataIndex]) }),
        formattedValue
    });
}

const cases = [
    [tooltipItem('Archive', [12, 24.75], 1, '24.75'), 'Archive: 24.75'],
    [tooltipItem('Zero', [0, 17], 0, '0'), 'Zero: 0'],
    [tooltipItem('Negative', [4, -12.5], 1, '-12.5'), 'Negative: -12.5'],
    [tooltipItem('Localized', [1234.5], 0, '1.234,5'), 'Localized: 1.234,5'],
    [tooltipItem('Custom', ['8.25'], 0, '8.25'), 'Custom: 8.25'],
    [tooltipItem('', [5], 0, '5'), '5'],
    [tooltipItem(undefined, [0], 0, '0'), '0']
];

for (const [item, expected] of cases) {
    assert.equal(labelCallback(item), expected, 'Radar tooltips must retain the dataset label and formatted value.');
}

console.log('JSLive Radar tooltip contracts verified (7 cases).');
