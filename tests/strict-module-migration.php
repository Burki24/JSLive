<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$inventoryPath = __DIR__ . '/fixtures/strict-module-public-methods.json';
$documentationPath = $root . '/docs/STRICT_MODULE_MIGRATION.md';

if (!is_file($inventoryPath)) {
    throw new RuntimeException('Missing strict-module public-method inventory.');
}
if (!is_file($documentationPath)) {
    throw new RuntimeException('Missing docs/STRICT_MODULE_MIGRATION.md.');
}

$inventory = json_decode(
    file_get_contents($inventoryPath),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$documentation = file_get_contents($documentationPath);
if ($documentation === false) {
    throw new RuntimeException('Unable to read strict-module migration documentation.');
}

$expectedSources = $inventory['sources'] ?? null;
if (!is_array($expectedSources) || $expectedSources === []) {
    throw new RuntimeException('Strict-module inventory has no sources.');
}

$methodCount = 0;
foreach ($expectedSources as $relativePath => $expectedMethods) {
    $sourcePath = $root . '/' . $relativePath;
    $source = is_file($sourcePath) ? file_get_contents($sourcePath) : false;
    if ($source === false) {
        throw new RuntimeException('Unable to read inventoried source ' . $relativePath . '.');
    }

    preg_match_all('/^\s*public function\s+([A-Za-z0-9_]+)\s*\(/m', $source, $matches);
    $actualMethods = $matches[1];
    $inventoriedMethods = array_keys($expectedMethods);
    sort($actualMethods);
    sort($inventoriedMethods);

    if ($actualMethods !== $inventoriedMethods) {
        throw new RuntimeException('Public-method inventory is stale for ' . $relativePath . '.');
    }

    if (!str_contains($documentation, '`' . $relativePath . '`')) {
        throw new RuntimeException('Migration documentation is missing source ' . $relativePath . '.');
    }

    foreach ($expectedMethods as $method => $targetSignature) {
        if (!is_string($targetSignature) || !str_contains($targetSignature, 'function ' . $method . '(')) {
            throw new RuntimeException('Invalid target signature for ' . $relativePath . '::' . $method . '.');
        }

        if ($method !== '__construct' && !preg_match('/\)\s*:\s*[?A-Za-z|]+$/', $targetSignature)) {
            throw new RuntimeException('Missing target return type for ' . $relativePath . '::' . $method . '.');
        }

        preg_match('/\((.*)\)/', $targetSignature, $parameterMatch);
        $parameters = trim($parameterMatch[1] ?? '');
        if ($parameters !== '') {
            foreach (explode(',', $parameters) as $parameter) {
                if (!preg_match('/^\s*[?A-Za-z|]+\s+&?\$[A-Za-z_][A-Za-z0-9_]*/', trim($parameter))) {
                    throw new RuntimeException('Missing target parameter type for ' . $relativePath . '::' . $method . '.');
                }
            }
        }
        $methodCount++;
    }
}

foreach ([
    'RegisterVariable*',
    '$this->SetValue',
    'GetCompatibleParents()',
    'bin2hex',
    'hex2bin',
    'RegisterHook()',
    'ProcessHookData(): void',
    'UnregisterHook()'
] as $requiredBoundary) {
    if (!str_contains($documentation, $requiredBoundary)) {
        throw new RuntimeException('Migration documentation is missing boundary ' . $requiredBoundary . '.');
    }
}

if ($methodCount !== 81) {
    throw new RuntimeException('Expected 81 inventoried public methods, got ' . $methodCount . '.');
}

fwrite(STDOUT, "JSLive IPSModuleStrict migration inventory is complete.\n");
