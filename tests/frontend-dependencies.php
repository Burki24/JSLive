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
    'jQuery 4.0.0',
    'Chart.js 4.3.3',
    'Chart.js 4.4.1',
    'Chart.js 4.5.1',
    'Chart.js 3.9.1',
    'Chart.js 3.6.0',
    'chartjs-adapter-moment 1.0.0',
    'chartjs-adapter-moment 1.0.1',
    'chartjs-plugin-datalabels 2.2.0',
    'chartjs-plugin-streaming 3.1.0',
    'chartjs-plugin-streaming 3.6.0',
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

$loadingBarDirectory = $root . '/SymconJSLive/js/loading-Bar/';
$loadingBarSources = is_file($loadingBarDirectory . 'SOURCES.md')
    ? file_get_contents($loadingBarDirectory . 'SOURCES.md') : false;
foreach ([
    'loading-bar.js'  => '6edf7feefaa7ae547fe7674ffb66d746e35a9e10a66fe6d2221a4ee3b7dcbe16',
    'loading-bar.css' => 'b42f3187ec8aa70fa17024cb5260ccd481bbaddf70ae0423b44c5c0fc2541131',
    'LICENSE'         => 'ddefaa5e04ba32fe6f7a8b3a152b8f1f4499bb07e9d5cc727a6b584b22656d53'
] as $loadingBarAsset => $expectedHash) {
    $loadingBarContent = file_get_contents($loadingBarDirectory . $loadingBarAsset);
    if (hash('sha256', str_replace("\r\n", "\n", $loadingBarContent)) !== $expectedHash) {
        throw new RuntimeException('Loading Bar audited asset changed: ' . $loadingBarAsset);
    }
    if ($loadingBarSources === false || !str_contains($loadingBarSources, $expectedHash)) {
        throw new RuntimeException('Loading Bar needs source and integrity records for ' . $loadingBarAsset);
    }
}

$gaugeHash = '44b0a4ac54e0b980371e8788f7ce8215dab5a2181cda460fc344276b50385904';
$gaugeBundle = file_get_contents($root . '/SymconJSLive/js/canvas-gauges/gauge.min.js');
if (hash('sha256', str_replace("\r\n", "\n", $gaugeBundle)) !== $gaugeHash) {
    throw new RuntimeException('Canvas Gauges must remain the verified upstream 2.1.7 distribution.');
}
$gaugeSourcesPath = $root . '/SymconJSLive/js/canvas-gauges/SOURCES.md';
if (!is_file($gaugeSourcesPath) || !str_contains(file_get_contents($gaugeSourcesPath), $gaugeHash)) {
    throw new RuntimeException('Canvas Gauges needs a source and integrity record.');
}
foreach (['Compass', 'Linear', 'Linear(vertical)', 'Radial'] as $gaugeTemplate) {
    $gaugeHtml = file_get_contents($root . '/SymconJSLive/templates/CanvasGauges-' . $gaugeTemplate . '.html');
    preg_match_all('#<script\b[^>]*\bsrc="([^"]*canvas-gauges[^"\s]*)"#i', $gaugeHtml, $gaugeScripts);
    if ($gaugeScripts[1] !== ['/hook/JSLive/js/canvas-gauges/gauge.min.js']) {
        throw new RuntimeException('Gauge templates must retain the existing verified asset URL.');
    }
}

$jqueryDirectory = $root . '/SymconJSLive/js/jquery/4.0.0/';
$jquerySources = is_file($jqueryDirectory . 'SOURCES.md')
    ? file_get_contents($jqueryDirectory . 'SOURCES.md') : false;
foreach ([
    'jquery.min.js'  => '2526ee3df5d907ad4374102b8cfbec025e5992f99fe9abbb0e9b22cb23beb861',
    'jquery.min.map' => 'e2f9377576b10edc8ca4ec3e6399b59ae1bd3a89b9248ce9026098377d70406c',
    'LICENSE.txt'    => 'd4db9ebe6f29f5168eac45ad713f055623ac5d0dcd5ba92da23d650ae012020d'
] as $jqueryAsset => $expectedHash) {
    if (!is_file($jqueryDirectory . $jqueryAsset) || hash_file('sha256', $jqueryDirectory . $jqueryAsset) !== $expectedHash) {
        throw new RuntimeException('Missing or modified jQuery distribution file: ' . $jqueryAsset . '.');
    }
    if ($jquerySources === false || !str_contains($jquerySources, $jqueryAsset) || !str_contains($jquerySources, $expectedHash)) {
        throw new RuntimeException('jQuery source inventory must record ' . $jqueryAsset . ' and its SHA-256.');
    }
}
$legacyJquery = file_get_contents($root . '/SymconJSLive/js/jquery.min.js');
if (hash('sha256', str_replace("\r\n", "\n", $legacyJquery)) !== 'ff1523fb7389539c84c65aba19260648793bb4f5e29329d2ee8804bc37a3fe6e') {
    throw new RuntimeException('The historical jQuery 3.6.0 compatibility asset must remain unchanged.');
}
foreach ($templateDirectories as $templateDirectory) {
    foreach (glob($templateDirectory . '/*.html') ?: [] as $templatePath) {
        preg_match_all('#<script\b[^>]*\bsrc="([^"]*jquery[^"\s]*)"#i', file_get_contents($templatePath), $jqueryScripts);
        if ($jqueryScripts[1] !== ['/hook/JSLive/js/jquery/4.0.0/jquery.min.js']) {
            throw new RuntimeException(basename($templatePath) . ' must load the full jQuery 4.0.0 bundle exactly once.');
        }
    }
}

$sharedChartReference = '/hook/JSLive/js/chartjs/4.5.1/chart.umd.min.js';
foreach (['Chart.html', 'Doughnut-PIE.html', 'RadarChart.html'] as $chartTemplate) {
    $template = file_get_contents($root . '/SymconJSLive/templates/' . $chartTemplate);
    preg_match_all('#<script\b[^>]*\bsrc="(/hook/JSLive/js/chartjs/(?!plugins/)[^"]+\.js)"#i', $template, $chartScripts);
    if ($chartScripts[1] !== [$sharedChartReference]) {
        throw new RuntimeException($chartTemplate . ' must load the shared Chart.js bundle exactly once.');
    }
    $datalabelsReference = '/hook/JSLive/js/chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js';
    preg_match_all('#<script\b[^>]*\bsrc="([^"]*chartjs-plugin-datalabels[^"\s]*)"#i', $template, $datalabelsScripts);
    if ($datalabelsScripts[1] !== [$datalabelsReference]) {
        throw new RuntimeException($chartTemplate . ' must load the official Datalabels bundle exactly once.');
    }
    if (strpos($template, $sharedChartReference) >= strpos($template, $datalabelsReference)) {
        throw new RuntimeException($chartTemplate . ' must load Chart.js before Datalabels.');
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

$adapterSources = file_get_contents($root . '/SymconJSLive/js/chartjs/plugins/moment/1.0.1/SOURCES.md');
foreach ([
    'chartjs-adapter-moment.min.js' => '4ca6ddbc16c438c7decc60f16fbee9639d37277af609390f7794eb2729addb55',
    'LICENSE.md'                    => 'b4b8355c2cd2b18354980a0c6422181d7bd6e895d94ae88b3570e97c60eea03d'
] as $adapterAsset => $expectedHash) {
    $assetPath = $root . '/SymconJSLive/js/chartjs/plugins/moment/1.0.1/' . $adapterAsset;
    if (!is_file($assetPath) || hash_file('sha256', $assetPath) !== $expectedHash) {
        throw new RuntimeException('Missing or modified Moment adapter distribution file: ' . $adapterAsset . '.');
    }
    if ($adapterSources === false || !str_contains($adapterSources, $adapterAsset) || !str_contains($adapterSources, $expectedHash)) {
        throw new RuntimeException('Moment adapter source inventory must record ' . $adapterAsset . ' and its SHA-256.');
    }
}

$datalabelsDirectory = $root . '/SymconJSLive/js/chartjs/plugins/datalabels/2.2.0/';
$datalabelsSources = is_file($datalabelsDirectory . 'SOURCES.md')
    ? file_get_contents($datalabelsDirectory . 'SOURCES.md') : false;
foreach ([
    'chartjs-plugin-datalabels.min.js' => '20c08f3d9c6d2ef76df6d6a6f1127c0013339fe32add24222276c398c6308c38',
    'LICENSE.md'                       => '075bb10eabebc9356311ffca1b18fdd470fca8e5c2ce0f6e098430c81c59a624'
] as $datalabelsAsset => $expectedHash) {
    $assetPath = $datalabelsDirectory . $datalabelsAsset;
    if (!is_file($assetPath) || hash_file('sha256', $assetPath) !== $expectedHash) {
        throw new RuntimeException('Missing or modified Datalabels distribution file: ' . $datalabelsAsset . '.');
    }
    if ($datalabelsSources === false || !str_contains($datalabelsSources, $datalabelsAsset)
        || !str_contains($datalabelsSources, $expectedHash)) {
        throw new RuntimeException('Datalabels source inventory must record ' . $datalabelsAsset . ' and its SHA-256.');
    }
}
// The historical URL has three local element-detection patches. Do not replace
// it silently for custom templates; tolerate checkout-only CRLF conversion.
$legacyDatalabels = file_get_contents($root . '/SymconJSLive/js/chartjs/plugins/chartjs-plugin-datalabels.min.js');
if ($legacyDatalabels === false
    || hash('sha256', str_replace("\r\n", "\n", $legacyDatalabels)) !== 'b990332d6a689719ce37714c499f51523c4ffa830b426948a60ebe9b020a25cc') {
    throw new RuntimeException('The historical Datalabels compatibility asset must remain unchanged.');
}

$streamingReference = '/hook/JSLive/js/chartjs/plugins/streaming/3.6.0/chartjs-plugin-streaming.min.js';
$chartTemplate = file_get_contents($root . '/SymconJSLive/templates/Chart.html');
preg_match_all('#<script\b[^>]*\bsrc="([^"]*chartjs-plugin-streaming[^"\s]*)"#i', $chartTemplate, $streamingScripts);
if ($streamingScripts[1] !== [$streamingReference]) {
    throw new RuntimeException('Chart.html must load the maintained streaming bundle exactly once.');
}
if (strpos($chartTemplate, $sharedChartReference) >= strpos($chartTemplate, $streamingReference)
    || strpos($chartTemplate, '/hook/JSLive/js/chartjs/plugins/moment/1.0.1/chartjs-adapter-moment.min.js') >= strpos($chartTemplate, $streamingReference)) {
    throw new RuntimeException('Chart.js and the Moment adapter must load before streaming.');
}
$streamingDirectory = $root . '/SymconJSLive/js/chartjs/plugins/streaming/3.6.0/';
$streamingSources = is_file($streamingDirectory . 'SOURCES.md')
    ? file_get_contents($streamingDirectory . 'SOURCES.md') : false;
foreach ([
    'chartjs-plugin-streaming.min.js'  => 'ae787d33e000a9b0abc21eb95bd5d86613587e3ad7d6dcdb8e07dd271c46d058',
    'LICENSE.md'                       => 'f73f043ba331cd7327bfb1cbb186820651d878ce7802c82928d65cd9e3a1ecf5'
] as $streamingAsset => $expectedHash) {
    $assetPath = $streamingDirectory . $streamingAsset;
    if (!is_file($assetPath) || hash_file('sha256', $assetPath) !== $expectedHash) {
        throw new RuntimeException('Missing or modified streaming distribution file: ' . $streamingAsset . '.');
    }
    if ($streamingSources === false || !str_contains($streamingSources, $streamingAsset)
        || !str_contains($streamingSources, $expectedHash)) {
        throw new RuntimeException('Streaming source inventory must record ' . $streamingAsset . ' and its SHA-256.');
    }
}
$legacyStreaming = file_get_contents($root . '/SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js');
if ($legacyStreaming === false
    || hash('sha256', str_replace("\r\n", "\n", $legacyStreaming)) !== '2e0ac91691bc76ff2c618c7d600a36cc3a10784ef34cd30ac968d72d6d15d839') {
    throw new RuntimeException('The historical streaming compatibility asset must remain unchanged.');
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

