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

        $signaturePattern = preg_quote($targetSignature, '/');
        $signaturePattern = str_replace('\\ ', '\\s+', $signaturePattern);
        if (!preg_match('/^\\s*' . $signaturePattern . '\\s*\\{/m', $source)) {
            throw new RuntimeException(
                'Strict target signature is not implemented for '
                . $relativePath
                . '::'
                . $method
                . ': '
                . $targetSignature
                . '.'
            );
        }
        $methodCount++;
    }
}

$webHookSource = file_get_contents($root . '/SymconJSLive/libs/WebHookModule.php');
$baseModuleSource = file_get_contents($root . '/SymconJSLive/libs/JSLiveModule.php');
if ($webHookSource === false || $baseModuleSource === false) {
    throw new RuntimeException('Unable to read the JSLive base classes.');
}

if (!str_contains($webHookSource, 'class WebHookModule extends IPSModuleStrict')) {
    throw new RuntimeException('WebHookModule must extend IPSModuleStrict.');
}
if (!str_contains($baseModuleSource, 'class JSLiveModule extends IPSModuleStrict')) {
    throw new RuntimeException('JSLiveModule must extend IPSModuleStrict.');
}
if (str_contains($webHookSource, 'private function RegisterHook')) {
    throw new RuntimeException('The manual WebHook Control registration workaround must be removed.');
}
if (!str_contains($webHookSource, '$this->RegisterHook($this->hook);')) {
    throw new RuntimeException('WebHookModule must use the native RegisterHook API.');
}
if (str_contains($webHookSource, 'IPS_KERNELMESSAGE')) {
    throw new RuntimeException('Native hook registration must not retain the Kernel-Ready workaround.');
}
if (!preg_match('/protected function\\s+ProcessHookData\\(\\)\\s*:\\s*void/', $webHookSource)) {
    throw new RuntimeException('WebHookModule must implement the strict ProcessHookData(): void signature.');
}

foreach (array_keys($expectedSources) as $relativePath) {
    if (!str_starts_with($relativePath, 'SymconJSLive') || $relativePath === 'SymconJSLive/module.php') {
        continue;
    }

    $source = file_get_contents($root . '/' . $relativePath);
    if ($source === false) {
        throw new RuntimeException('Unable to read ' . $relativePath . '.');
    }
    if (str_contains($source, 'ConnectParent(')) {
        throw new RuntimeException($relativePath . ' must use strict automatic parent compatibility.');
    }
}

$variableSources = [
    'SymconJSLive/libs/JSLiveModule.php',
    'SymconJSLiveAdvTextfield/module.php',
    'SymconJSLiveChart/module.php',
    'SymconJSLiveRadarChart/module.php'
];
foreach ($variableSources as $relativePath) {
    $source = file_get_contents($root . '/' . $relativePath);
    if ($source === false) {
        throw new RuntimeException('Unable to read ' . $relativePath . '.');
    }
    if (preg_match('/RegisterVariable(?:Boolean|Integer|Float|String)\\([^;]+,\\s*[\'\"][^\'\"]*[\'\"]\\s*,\\s*\\d+\\s*\\)/', $source)) {
        throw new RuntimeException($relativePath . ' still registers a variable with a profile string.');
    }
}

$allProductionSources = '';
foreach (array_keys($expectedSources) as $relativePath) {
    $source = file_get_contents($root . '/' . $relativePath);
    if ($source === false) {
        throw new RuntimeException('Unable to read ' . $relativePath . '.');
    }
    $allProductionSources .= $source;
}
if (str_contains($allProductionSources, 'bin2hex(') || str_contains($allProductionSources, 'hex2bin(')) {
    throw new RuntimeException('JSLive JSON data-flow buffers must not be treated as binary payloads.');
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

if ($methodCount !== 74) {
    throw new RuntimeException('Expected 74 inventoried public methods, got ' . $methodCount . '.');
}

fwrite(STDOUT, "JSLive IPSModuleStrict migration inventory is complete.\n");
