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
$externalRuntimeReferences = [];
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
                $externalRuntimeReferences[$reference] = basename($templatePath);
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

if ($externalRuntimeReferences !== []) {
    throw new RuntimeException(
        'Built-in templates still contain external runtime resources: '
        . implode(', ', array_keys($externalRuntimeReferences))
        . '.'
    );
}

$fontSourcesPath = $root . '/SymconJSLive/js/fonts/SOURCES.md';
$fontSources = is_file($fontSourcesPath) ? file_get_contents($fontSourcesPath) : false;
if ($fontSources === false) {
    throw new RuntimeException('Missing SymconJSLive/js/fonts/SOURCES.md.');
}
$fontReferences = [];
foreach (glob($root . '/SymconJSLive/js/css/fonts/*.css') ?: [] as $fontStylesheet) {
    $stylesheet = file_get_contents($fontStylesheet);
    if ($stylesheet === false) {
        throw new RuntimeException('Cannot read font stylesheet ' . $fontStylesheet . '.');
    }

    if (str_contains($stylesheet, 'fonts.gstatic.com')) {
        throw new RuntimeException(basename($fontStylesheet) . ' still loads a remote Google Font.');
    }
    if (preg_match('#url\(["\']?(/hook/JSLive/js/fonts/[^)"\']+\.woff2)["\']?\)#', $stylesheet, $match) !== 1) {
        throw new RuntimeException(basename($fontStylesheet) . ' has no local WOFF2 source.');
    }

    $fontReferences[$match[1]] = true;
}

if (count($fontReferences) !== 20) {
    throw new RuntimeException('Frontend inventory must contain exactly 20 locally vendored font files.');
}
foreach (array_keys($fontReferences) as $fontReference) {
    $fontPath = $root . '/SymconJSLive'
        . str_replace('/', DIRECTORY_SEPARATOR, substr($fontReference, strlen('/hook/JSLive')));
    $fontData = is_file($fontPath) ? file_get_contents($fontPath) : false;
    if ($fontData === false || strlen($fontData) < 4 || substr($fontData, 0, 4) !== 'wOF2') {
        throw new RuntimeException('Missing or invalid WOFF2 font asset: ' . $fontReference . '.');
    }
    if (!str_contains($fontSources, basename($fontPath))) {
        throw new RuntimeException('Font source inventory is missing local asset ' . basename($fontPath) . '.');
    }
    if (!str_contains($fontSources, hash('sha256', $fontData))) {
        throw new RuntimeException('Font source inventory has no matching SHA-256 for ' . basename($fontPath) . '.');
    }
}

foreach (glob($root . '/SymconJSLive/js/css/*.css') ?: [] as $stylesheetPath) {
    $stylesheet = file_get_contents($stylesheetPath);
    if ($stylesheet !== false && str_contains($stylesheet, 'fonts.gstatic.com')) {
        throw new RuntimeException(basename($stylesheetPath) . ' still loads a remote Google Font.');
    }
}

$colorPicker = file_get_contents($root . '/SymconJSLive/templates/ColorPicker.html');
if ($colorPicker === false) {
    throw new RuntimeException('Cannot read the ColorPicker template.');
}
if (!str_contains($colorPicker, '<script src="/hook/JSLive/js/iro/5.5.0/iro.js"></script>')) {
    throw new RuntimeException('ColorPicker must load the pinned local iro.js 5.5.0 asset.');
}
if (str_contains($colorPicker, 'cdn.jsdelivr.net')) {
    throw new RuntimeException('ColorPicker still loads iro.js from jsDelivr.');
}

foreach ([
    'SymconJSLive/js/fonts/SOURCES.md',
    'SymconJSLive/js/fonts/licenses/Apache-2.0.txt',
    'SymconJSLive/js/iro/5.5.0/LICENSE.txt'
] as $licensePath) {
    if (!is_file($root . '/' . $licensePath)) {
        throw new RuntimeException('Missing vendored frontend license/source file ' . $licensePath . '.');
    }
}

$fontLicensePaths = glob($root . '/SymconJSLive/js/fonts/licenses/*-OFL.txt') ?: [];
if (count($fontLicensePaths) !== 17) {
    throw new RuntimeException('Expected one OFL license file for each of the 17 OFL font families.');
}
preg_match_all('#`(licenses/[^`]+\.txt)`#', $fontSources, $fontLicenseMatches);
$referencedFontLicenses = array_values(array_unique($fontLicenseMatches[1]));
if (count($referencedFontLicenses) !== 18) {
    throw new RuntimeException('Font source inventory must reference all 18 vendored license files.');
}
foreach ($referencedFontLicenses as $referencedFontLicense) {
    if (!is_file($root . '/SymconJSLive/js/fonts/' . $referencedFontLicense)) {
        throw new RuntimeException('Font source inventory references a missing license: ' . $referencedFontLicense . '.');
    }
}

$iroAsset = file_get_contents($root . '/SymconJSLive/js/iro/5.5.0/iro.js');
if ($iroAsset === false || !str_contains(substr($iroAsset, 0, 200), 'iro.js v5.5.0')) {
    throw new RuntimeException('The local ColorPicker dependency must remain pinned to iro.js 5.5.0.');
}

fwrite(STDOUT, "JSLive frontend dependency inventory is complete.\n");

