<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/validate_structure.php',
    __DIR__ . '/public-contracts.php',
    __DIR__ . '/connect-address.php',
    __DIR__ . '/webhook-routing.php',
    __DIR__ . '/webhook-security-model.php',
    __DIR__ . '/runtime-matrix.php',
    __DIR__ . '/frontend-dependencies.php',
    __DIR__ . '/strict-module-migration.php',
    __DIR__ . '/configuration-transfer.php',
    __DIR__ . '/configuration-form.php',
    __DIR__ . '/public-form-callbacks.php',
    __DIR__ . '/adv-textfield-rendering.php',
    __DIR__ . '/visualization-output-contracts.php',
    __DIR__ . '/gauge-rendering.php',
    __DIR__ . '/datetime-rendering.php',
    __DIR__ . '/datetime-configuration-copy.php',
    __DIR__ . '/radar-chart-dates.php',
    __DIR__ . '/chart-dates.php',
    __DIR__ . '/custom-data.php',
    __DIR__ . '/data-flow-integration.php',
    __DIR__ . '/child-routing-filter.php',
    __DIR__ . '/message-sink-registration.php',
    __DIR__ . '/http-response-integration.php',
    __DIR__ . '/splitter-debug-helper.php',
    __DIR__ . '/jslive-module-debug-helper.php',
    __DIR__ . '/debug-log-access.php',
    __DIR__ . '/gauge-progressbar-debug.php',
    __DIR__ . '/color-datetime-debug.php',
    __DIR__ . '/adv-debug.php',
    __DIR__ . '/doughnut-debug.php',
    __DIR__ . '/custom-debug.php',
    __DIR__ . '/radar-debug.php',
    __DIR__ . '/chart-debug.php',
    __DIR__ . '/full-debug-payload.php'
];

$commands = [
    ['Test Progressbar rendering', 'node tests/progressbar-rendering.js'],
    ['Test Loading Bar animation', 'node tests/loading-bar-animation.js'],
    ['Verify Canvas Gauges patch', 'node scripts/build-gauge-patch.js --check'],
    ['Test Gauge animation', 'node tests/gauge-animation.js'],
    ['Test Radar tooltip', 'node tests/radar-tooltip.js'],
    ['Test Chart asynchronous loading', 'node tests/chart-async-loading.js'],
    ['Test Chart Streaming integration', 'node tests/chart-streaming.js'],
    ['Test isolated realtime window prototype', 'node tests/realtime-window.js'],
    ['Test Moment adapter', 'node tests/moment-adapter.js'],
    ['Verify vendored helper integrity', 'python3 tests/helper_integrity.py'],
    ['Test library metadata updater', 'python3 tests/test_update_library_metadata.py']
];

foreach ($tests as $test) {
    echo 'Running ' . basename($test) . "...\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($test), $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

foreach ($commands as [$label, $command]) {
    echo $label . "...\n";
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

echo "All JSLive tests passed.\n";
