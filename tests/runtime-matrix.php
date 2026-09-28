<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$matrixPath = $root . '/docs/SYCON_RUNTIME_MATRIX.md';
$matrix = is_file($matrixPath) ? file_get_contents($matrixPath) : false;
if ($matrix === false) {
    throw new RuntimeException('Missing docs/SYCON_RUNTIME_MATRIX.md.');
}

foreach (['S90-FRESH', 'S90-UPGRADE', 'S91-FRESH', 'S91-UPGRADE'] as $scenario) {
    if (!str_contains($matrix, '`' . $scenario . '`')) {
        throw new RuntimeException('Runtime matrix is missing scenario ' . $scenario . '.');
    }
}

$contracts = json_decode(
    file_get_contents(__DIR__ . '/fixtures/public-contracts.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
foreach (array_keys($contracts['modules']) as $moduleDirectory) {
    if (!str_contains($matrix, '| `' . $moduleDirectory . '` |')) {
        throw new RuntimeException('Runtime matrix is missing module ' . $moduleDirectory . '.');
    }
}

foreach ([
    'IP-Symcon version and build',
    'PHP version',
    'JSLive commit',
    'Fresh installation',
    'Upgrade installation',
    'ApplyChanges twice',
    'Service restart',
    'Message log'
] as $requiredEvidence) {
    if (!str_contains($matrix, $requiredEvidence)) {
        throw new RuntimeException('Runtime matrix is missing evidence field: ' . $requiredEvidence . '.');
    }
}

fwrite(STDOUT, "JSLive Symcon runtime matrix is complete.\n");
