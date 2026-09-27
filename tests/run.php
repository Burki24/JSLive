<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/validate_structure.php',
    __DIR__ . '/public-contracts.php',
    __DIR__ . '/webhook-routing.php',
    __DIR__ . '/configuration-transfer.php',
    __DIR__ . '/configuration-form.php',
    __DIR__ . '/adv-textfield-rendering.php',
    __DIR__ . '/radar-chart-dates.php',
    __DIR__ . '/chart-dates.php',
    __DIR__ . '/custom-data.php',
    __DIR__ . '/config-store-contracts.php',
    __DIR__ . '/data-flow-integration.php',
    __DIR__ . '/sync-debug-helper.php'
];

$commands = [
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
