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
    'Chart.js 4.5.1',
    'Chart.js 3.9.1',
    'Chart.js 3.6.0',
    'chartjs-adapter-moment 1.0.0',
    'chartjs-plugin-datalabels 2.2.0',
    'chartjs-plugin-streaming 3.1.0',
    'Moment.js 2.27.0',
    'Moment.js 2.31.0',
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

$sharedChartReference = '/hook/JSLive/js/chartjs/4.5.1/chart.umd.min.js';
foreach (['Chart.html', 'Doughnut-PIE.html', 'RadarChart.html'] as $chartTemplate) {
    $template = file_get_contents($root . '/SymconJSLive/templates/' . $chartTemplate);
    preg_match_all('#<script\b[^>]*\bsrc="(/hook/JSLive/js/chartjs/(?!plugins/)[^"]+\.js)"#i', $template, $chartScripts);
    if ($chartScripts[1] !== [$sharedChartReference]) {
        throw new RuntimeException($chartTemplate . ' must load the shared Chart.js bundle exactly once.');
    }
}
foreach (['4.5.1/chart.umd.min.js' => '4.5.1', 'chart.min.js' => '4.4.1', 'chart.js' => '4.3.3'] as $chartAsset => $version) {
    $asset = file_get_contents($root . '/SymconJSLive/js/chartjs/' . $chartAsset);
    if (!str_contains(substr($asset, 0, 512), 'Chart.js v' . $version)) {
        throw new RuntimeException($chartAsset . ' must retain its pinned Chart.js version ' . $version . '.');
    }
}

$chartSources = file_get_contents($root . '/SymconJSLive/js/chartjs/4.5.1/SOURCES.md');
foreach ([
    'chart.umd.min.js'     => '48444a82d4edcb5bec0f1965faacdde18d9c17db3063d042abada2f705c9f54a',
    'chart.umd.min.js.map' => 'fecb66dd71acd07201280ad726d94a0e45f0c42762ac1d47fa1132f8af3bff25',
    'LICENSE.md'           => '41a84aa2caba645f966a18d9c2056b73e6d3a81d80bc0046bc0011a2634d4cce',
    'KURKLE-LICENSE.md'    => '2859c50313bad2ba77b081410c477beaf92b60ca13a77a248d89859a6dd6ac81'
] as $chartAsset => $expectedHash) {
    $assetPath = $root . '/SymconJSLive/js/chartjs/4.5.1/' . $chartAsset;
    if (!is_file($assetPath) || hash_file('sha256', $assetPath) !== $expectedHash) {
        throw new RuntimeException('Missing or modified Chart.js distribution file: ' . $chartAsset . '.');
    }
    if ($chartSources === false || !str_contains($chartSources, $chartAsset) || !str_contains($chartSources, $expectedHash)) {
        throw new RuntimeException('Chart.js source inventory must record ' . $chartAsset . ' and its SHA-256.');
    }
}

$momentSources = file_get_contents($root . '/SymconJSLive/js/moment/2.31.0/SOURCES.md');
foreach ([
    'moment.min.js'     => 'db2cf339996ce8387e2750fabfe5161c1418b204f6f197167a09cfe5d6655892',
    'moment.min.js.map' => '3a971634e403e4b43b35b1f857e4f8750f0f17121beab40aacb1a63ad8557887',
    'LICENSE'           => '64419cc68debfd9b7c27e9cd926c756181e0857709d2701094874e0fb1a41d28'
] as $momentAsset => $expectedHash) {
    $assetPath = $root . '/SymconJSLive/js/moment/2.31.0/' . $momentAsset;
    if (!is_file($assetPath) || hash_file('sha256', $assetPath) !== $expectedHash) {
        throw new RuntimeException('Missing or modified Moment.js distribution file: ' . $momentAsset . '.');
    }
    if ($momentSources === false || !str_contains($momentSources, $momentAsset) || !str_contains($momentSources, $expectedHash)) {
        throw new RuntimeException('Moment.js source inventory must record ' . $momentAsset . ' and its SHA-256.');
    }
}

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
    if (str_starts_with($reference, 'SymconJSLive/js/') && !is_file($root . '/' . $reference)) {
        throw new RuntimeException('Missing local asset ' . $reference . ' referenced by ' . $template . '.');
    }
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

