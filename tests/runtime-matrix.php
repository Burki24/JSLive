<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$matrixPath = $root . '/docs/SYCON_RUNTIME_MATRIX.md';
$matrix = is_file($matrixPath) ? file_get_contents($matrixPath) : false;
if ($matrix === false) {
    throw new RuntimeException('Missing docs/SYCON_RUNTIME_MATRIX.md.');
}

if (!str_contains($matrix, '`MCP-CURRENT`')) {
    throw new RuntimeException('Runtime matrix is missing the MCP-CURRENT test plane.');
}
foreach (['S90-FRESH', 'S90-UPGRADE', 'S91-FRESH', 'S91-UPGRADE'] as $retiredScenario) {
    if (str_contains($matrix, '`' . $retiredScenario . '`')) {
        throw new RuntimeException('Runtime matrix still contains retired scenario ' . $retiredScenario . '.');
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
    'Test plane',
    'Update path',
    'ApplyChanges twice',
    'Service restart',
    'Message log'
] as $requiredEvidence) {
    if (!str_contains($matrix, $requiredEvidence)) {
        throw new RuntimeException('Runtime matrix is missing evidence field: ' . $requiredEvidence . '.');
    }
}

fwrite(STDOUT, "JSLive Symcon runtime matrix is complete.\n");
