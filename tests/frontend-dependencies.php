<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$inventoryPath = $root . '/docs/FRONTEND_DEPENDENCIES.md';
$inventory = is_file($inventoryPath) ? file_get_contents($inventoryPath) : false;
if ($inventory === false) {
    throw new RuntimeException('Missing docs/FRONTEND_DEPENDENCIES.md.');
}

$contracts = json_decode(
    file_get_contents(__DIR__ . '/fixtures/public-contracts.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
foreach (array_keys($contracts['modules']) as $moduleDirectory) {
    if (!str_contains($inventory, '`' . $moduleDirectory . '`')) {
        throw new RuntimeException('Frontend inventory is missing module ' . $moduleDirectory . '.');
    }
}

foreach ([
    'jQuery 3.6.0',
    'Chart.js 4.3.3',
    'Chart.js 4.4.1',
    'Chart.js 3.9.1',
    'Chart.js 3.6.0',
    'chartjs-adapter-moment 1.0.0',
    'chartjs-plugin-datalabels 2.2.0',
    'chartjs-plugin-streaming 3.1.0',
    'Moment.js 2.27.0',
    'Canvas Gauges 2.1.7',
    'iro.js 5.5.0',
    'Loading Bar/ldBar',
    'MCDatepicker',
    'fonts.gstatic.com'
] as $dependency) {
    if (!str_contains($inventory, $dependency)) {
        throw new RuntimeException('Frontend inventory is missing dependency ' . $dependency . '.');
    }
}

$templateDirectories = [
    $root . '/SymconJSLive/templates',
    $root . '/SymconJSLive/htmlbox'
];
$references = [];
foreach ($templateDirectories as $templateDirectory) {
    foreach (glob($templateDirectory . '/*.html') ?: [] as $templatePath) {
        $template = file_get_contents($templatePath);
        if ($template === false) {
            throw new RuntimeException('Cannot read frontend template ' . $templatePath . '.');
        }

        preg_match_all('/\b(?:src|href)="([^"]+)"/i', $template, $matches);
        foreach ($matches[1] as $reference) {
            if (str_starts_with($reference, '/hook/JSLive/js/')) {
                $asset = explode('?', substr($reference, strlen('/hook/JSLive/js/')), 2)[0];
                $references['SymconJSLive/js/' . $asset] = basename($templatePath);
            } elseif (preg_match('#^https?://#i', $reference) === 1) {
                $references[$reference] = basename($templatePath);
            }
        }
    }
}

foreach ($references as $reference => $template) {
    if (!str_contains($inventory, $reference)) {
        throw new RuntimeException(
            'Frontend inventory is missing reference ' . $reference . ' from ' . $template . '.'
        );
    }
}

foreach (glob($root . '/SymconJSLive/js/css/fonts/*.css') ?: [] as $fontStylesheet) {
    $stylesheet = file_get_contents($fontStylesheet);
    if ($stylesheet === false) {
        throw new RuntimeException('Cannot read font stylesheet ' . $fontStylesheet . '.');
    }
    if (preg_match('#url\(https://fonts\.gstatic\.com/#', $stylesheet) !== 1) {
        throw new RuntimeException(basename($fontStylesheet) . ' has no inventoried Google Fonts source.');
    }
}

fwrite(STDOUT, "JSLive frontend dependency inventory is complete.\n");

