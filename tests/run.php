<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/validate_structure.php',
    __DIR__ . '/public-contracts.php',
    __DIR__ . '/data-flow-integration.php'
];

$commands = [
    ['Verify vendored helper integrity', 'python3 tests/helper_integrity.py']
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
