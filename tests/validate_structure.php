<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$expectedModules = [
    'SymconJSLive'                    => 2,
    'SymconJSLiveAdvTextfield'        => 3,
    'SymconJSLiveCalendar'            => 3,
    'SymconJSLiveChart'               => 3,
    'SymconJSLiveColorPicker'         => 3,
    'SymconJSLiveCustom'              => 3,
    'SymconJSLiveDateTimePicker'      => 3,
    'SymconJSLiveDoughnutPie'         => 3,
    'SymconJSLiveGauge'               => 3,
    'SymconJSLiveProgressbar'         => 3,
    'SymconJSLiveRadarChart'          => 3,
    'SymconJSLiveSyncModule'          => 3
];

$readmePath = $root . '/README.md';
$readme = file_get_contents($readmePath);
if ($readme === false) {
    $errors[] = 'Cannot read README.md';
} else {
    foreach (array_keys($expectedModules) as $moduleDirectory) {
        if (!str_contains($readme, '](' . $moduleDirectory . ')')) {
            $errors[] = 'README.md does not link module directory: ' . $moduleDirectory;
        }
    }
}

foreach ([
    'CHANGELOG.md',
    'docs/RELEASE_PROCESS.md',
    'docs/STRICT_MODULE_MIGRATION.md',
    'docs/SYCON_RUNTIME_MATRIX.md',
    'docs/WEBHOOK_SECURITY_MODEL.md'
] as $requiredDocumentation) {
    if (!is_file($root . '/' . $requiredDocumentation)) {
        $errors[] = 'Missing required project documentation: ' . $requiredDocumentation;
    }
}

$changelogPath = $root . '/CHANGELOG.md';
if (is_file($changelogPath)) {
    $changelog = file_get_contents($changelogPath);
    if ($changelog === false) {
        $errors[] = 'Cannot read CHANGELOG.md';
    } elseif (!str_contains($changelog, '## Unreleased')) {
        $errors[] = 'CHANGELOG.md must contain an Unreleased section.';
    }
}

/**
 * Reads a JSON object and records a useful validation error on failure.
 *
 * @param list<string> $errors
 *
 * @return array<string, mixed>|null
 */
function readJsonObject(string $path, array &$errors): ?array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = 'Cannot read ' . $path;

        return null;
    }

    try {
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $errors[] = $path . ': ' . $exception->getMessage();

        return null;
    }

    if (!is_array($data)) {
        $errors[] = $path . ' must contain a JSON object.';

        return null;
    }

    return $data;
}

/**
 * Checks an IP-Symcon GUID.
 */
function isSymconGuid(mixed $value): bool
{
    return is_string($value)
        && preg_match('/^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$/i', $value) === 1;
}

$libraryPath = $root . '/library.json';
$library = readJsonObject($libraryPath, $errors);
if ($library !== null) {
    foreach (['id', 'author', 'name', 'url', 'version', 'build', 'date'] as $key) {
        if (!array_key_exists($key, $library)) {
            $errors[] = 'library.json is missing required field: ' . $key;
        }
    }

    if (isset($library['id']) && !isSymconGuid($library['id'])) {
        $errors[] = 'library.json contains an invalid id.';
    }
    if (isset($library['version']) && (!is_string($library['version']) || preg_match('/^\d+\.\d+$/', $library['version']) !== 1)) {
        $errors[] = 'library.json version must use the Hauptversion.Nebenstand format.';
    }
    if (isset($library['build']) && (!is_int($library['build']) || $library['build'] < 0)) {
        $errors[] = 'library.json contains an invalid build number.';
    }
    if (isset($library['date']) && (!is_int($library['date']) || $library['date'] <= 0)) {
        $errors[] = 'library.json contains an invalid date.';
    }
}

$discoveredModules = [];
foreach (glob($root . '/*/module.json') ?: [] as $modulePath) {
    $discoveredModules[basename(dirname($modulePath))] = $modulePath;
}
ksort($discoveredModules);

$expectedNames = array_keys($expectedModules);
$actualNames = array_keys($discoveredModules);
sort($expectedNames);
sort($actualNames);
if ($actualNames !== $expectedNames) {
    $errors[] = 'Module inventory differs from the characterized dev baseline. Expected: '
        . implode(', ', $expectedNames) . '; found: ' . implode(', ', $actualNames);
}

$moduleIds = [];
$prefixes = [];
foreach ($discoveredModules as $directory => $modulePath) {
    if (!is_file(dirname($modulePath) . '/README.md')) {
        $errors[] = $directory . ' is missing README.md';
    }

    foreach (['module.php', 'form.json', 'locale.json'] as $requiredFile) {
        if (!is_file(dirname($modulePath) . '/' . $requiredFile)) {
            $errors[] = $directory . ' is missing ' . $requiredFile;
        }
    }

    $module = readJsonObject($modulePath, $errors);
    if ($module === null) {
        continue;
    }

    foreach (['id', 'name', 'type', 'vendor', 'parentRequirements', 'childRequirements', 'implemented', 'prefix'] as $key) {
        if (!array_key_exists($key, $module)) {
            $errors[] = $directory . '/module.json is missing required field: ' . $key;
        }
    }

    $id = $module['id'] ?? null;
    if (!isSymconGuid($id)) {
        $errors[] = $directory . '/module.json contains an invalid id.';
    } elseif (isset($moduleIds[$id])) {
        $errors[] = 'Duplicate module id in ' . $moduleIds[$id] . ' and ' . $directory . ': ' . $id;
    } else {
        $moduleIds[$id] = $directory;
    }

    $prefix = $module['prefix'] ?? null;
    if (!is_string($prefix) || preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $prefix) !== 1) {
        $errors[] = $directory . '/module.json contains an invalid prefix.';
    } elseif (isset($prefixes[$prefix])) {
        $errors[] = 'Duplicate module prefix in ' . $prefixes[$prefix] . ' and ' . $directory . ': ' . $prefix;
    } else {
        $prefixes[$prefix] = $directory;
    }

    $type = $module['type'] ?? null;
    if (!is_int($type) || !in_array($type, [0, 1, 2, 3, 4, 5, 6], true)) {
        $errors[] = $directory . '/module.json contains an invalid module type.';
    } elseif (isset($expectedModules[$directory]) && $type !== $expectedModules[$directory]) {
        $errors[] = $directory . '/module.json changed type from the characterized dev baseline.';
    }

    foreach (['parentRequirements', 'childRequirements', 'implemented'] as $requirementType) {
        $requirements = $module[$requirementType] ?? null;
        if (!is_array($requirements)) {
            $errors[] = $directory . '/module.json field ' . $requirementType . ' must be an array.';
            continue;
        }

        foreach ($requirements as $requirement) {
            if (!isSymconGuid($requirement)) {
                $errors[] = $directory . '/module.json contains an invalid GUID in ' . $requirementType . '.';
            }
        }
    }
}

$helperConfigPath = $root . '/.helper-sync.json';
$helperConfig = readJsonObject($helperConfigPath, $errors);
if ($helperConfig !== null) {
    if (($helperConfig['schema'] ?? null) !== 1) {
        $errors[] = '.helper-sync.json must use schema 1.';
    }
    if (($helperConfig['source_repository'] ?? null) !== 'Burki24/Symcon_ModuleHelper') {
        $errors[] = '.helper-sync.json contains an unexpected source repository.';
    }
    if (($helperConfig['base_branch'] ?? null) !== 'dev') {
        $errors[] = '.helper-sync.json must target the dev branch.';
    }

    $helpers = $helperConfig['helpers'] ?? null;
    if (!is_array($helpers) || $helpers === []) {
        $errors[] = '.helper-sync.json must subscribe to at least one helper.';
    } else {
        foreach ($helpers as $name => $subscription) {
            $expectedTarget = 'libs/helper/' . $name . '.php';
            if (!is_array($subscription) || ($subscription['target'] ?? null) !== $expectedTarget) {
                $errors[] = '.helper-sync.json contains an invalid target for ' . $name . '.';
            }
        }
    }
}

$codeqlWorkflowPath = $root . '/.github/workflows/codeql-analysis.yml';
$codeqlWorkflow = file_get_contents($codeqlWorkflowPath);
if ($codeqlWorkflow === false) {
    $errors[] = 'Cannot read .github/workflows/codeql-analysis.yml';
} else {
    if (preg_match('/^\s*-\s+javascript-typescript\s*$/m', $codeqlWorkflow) !== 1) {
        $errors[] = 'CodeQL must analyze the JavaScript/TypeScript sources.';
    }
    if (preg_match('/^\s*-\s+php\s*$/m', $codeqlWorkflow) === 1) {
        $errors[] = 'CodeQL does not support PHP; PHP compatibility is checked by the Tests workflow.';
    }
}

$styleWorkflowPath = $root . '/.github/workflows/style.yml';
$styleWorkflow = file_get_contents($styleWorkflowPath);
if ($styleWorkflow === false) {
    $errors[] = 'Cannot read .github/workflows/style.yml';
} else {
    foreach ([
        'uses: actions/checkout@v6',
        'uses: Burki24/Symcon_ModuleCI/style@v1.0.0'
    ] as $requiredWorkflowContent) {
        if (!str_contains($styleWorkflow, $requiredWorkflowContent)) {
            $errors[] = '.github/workflows/style.yml is missing required content: ' . $requiredWorkflowContent;
        }
    }
}

foreach ([
    '.github/scripts/update_library_metadata.py',
    'tests/test_update_library_metadata.py'
] as $requiredMetadataFile) {
    if (!is_file($root . '/' . $requiredMetadataFile)) {
        $errors[] = 'Missing library metadata automation file: ' . $requiredMetadataFile;
    }
}

$metadataWorkflowPath = $root . '/.github/workflows/update-library-metadata.yml';
$metadataWorkflow = file_get_contents($metadataWorkflowPath);
if ($metadataWorkflow === false) {
    $errors[] = 'Cannot read .github/workflows/update-library-metadata.yml';
} else {
    foreach ([
        'branches:',
        '- dev',
        'concurrency:',
        "'CHORE: Update library metadata'",
        'uses: actions/create-github-app-token@v3',
        'client-id: ${{ vars.HELPER_SYNC_APP_CLIENT_ID }}',
        'private-key: ${{ secrets.HELPER_SYNC_APP_PRIVATE_KEY }}',
        'uses: actions/checkout@v6',
        'uses: Burki24/Symcon_ModuleCI/php-tests@v1.0.0',
        'python3 .github/scripts/update_library_metadata.py',
        'git push origin "HEAD:${GITHUB_REF_NAME}"'
    ] as $requiredWorkflowContent) {
        if (!str_contains($metadataWorkflow, $requiredWorkflowContent)) {
            $errors[] = '.github/workflows/update-library-metadata.yml is missing required content: '
                . $requiredWorkflowContent;
        }
    }
}

$testRunnerPath = $root . '/tests/run.php';
$testRunner = file_get_contents($testRunnerPath);
if ($testRunner === false) {
    $errors[] = 'Cannot read tests/run.php';
} elseif (!str_contains($testRunner, 'python3 tests/test_update_library_metadata.py')) {
    $errors[] = 'tests/run.php must execute the library metadata updater regression test.';
}

if ($errors !== []) {
    fwrite(STDERR, "JSLive structure validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo 'JSLive structure is valid (' . count($discoveredModules) . " modules).\n";
