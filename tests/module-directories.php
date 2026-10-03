<?php

declare(strict_types=1);

require_once __DIR__ . '/support/module-directories.php';

$fixture = sys_get_temp_dir() . '/jslive-module-directories-' . bin2hex(random_bytes(8));
$directories = [];
$files = [];
$checks = 0;

function checkDirectories(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

try {
    mkdir($fixture);
    $directories[] = $fixture;
    foreach (['libs', 'docs', 'imgs', 'tests', 'actions', '.github', '.style', '.custom', '.github/scripts', 'EmptyLocalLeftover', 'ExampleModule'] as $name) {
        $directory = $fixture . '/' . $name;
        mkdir($directory);
        $directories[] = $directory;
        if ($name !== 'EmptyLocalLeftover') {
            $file = $directory . ($name === 'ExampleModule' ? '/module.json' : '/fixture.txt');
            file_put_contents($file, '{}');
            $files[] = $file;
        }
    }
    $files[] = $fixture . '/README.md';
    file_put_contents($fixture . '/README.md', 'Root files are not modules.');
    $errors = [];
    $modules = discoverModuleDirectories($fixture, $errors);
    checkDirectories($errors === [], 'Documented exemptions, root files and empty local leftovers must be accepted.');
    checkDirectories(array_keys($modules) === ['ExampleModule'], 'Only the actual module must be discovered.');

    foreach (['scripts', 'OtherTooling', 'BrokenModule'] as $name) {
        $directory = $fixture . '/' . $name;
        mkdir($directory);
        $directories[] = $directory;
        $files[] = $directory . '/fixture.txt';
        file_put_contents($directory . '/fixture.txt', 'Not a module manifest.');
        $errors = [];
        discoverModuleDirectories($fixture, $errors);
        checkDirectories(
            count(array_filter($errors, static fn ($error) => str_starts_with($error, $name . '/module.json missing:'))) === 1,
            'Missing module.json must be rejected for ' . $name
        );
    }

    mkdir($fixture . '/BrokenModule/module.json');
    $directories[] = $fixture . '/BrokenModule/module.json';
    $errors = [];
    discoverModuleDirectories($fixture, $errors);
    checkDirectories(count($errors) === 3, 'A directory named module.json must not count as a manifest.');

    $files[] = $fixture . '/OtherTooling/module.json';
    file_put_contents($fixture . '/OtherTooling/module.json', '{}');
    $errors = [];
    $modules = discoverModuleDirectories($fixture, $errors);
    checkDirectories(
        array_keys($modules) === ['ExampleModule', 'OtherTooling'],
        'Unknown modules must remain visible to the existing inventory and manifest validation.'
    );
    checkDirectories(count($errors) === 2, 'Adding a manifest must remove only the corresponding missing-manifest error.');
} finally {
    // Only remove explicitly created fixture paths; never recurse into a tree.
    foreach (array_reverse($files) as $file) {
        unlink($file);
    }
    foreach (array_reverse($directories) as $directory) {
        rmdir($directory);
    }
}

echo 'Module directory regression tests passed (' . $checks . " checks).\n";
