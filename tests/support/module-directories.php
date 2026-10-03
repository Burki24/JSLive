<?php

declare(strict_types=1);

/**
 * Discover modules using Symcon's library root directory rules.
 * https://www.symcon.de/de/llms/developer/sdk-tools/sdk-php.md
 *
 * @param list<string> $errors
 *
 * @return array<string, string>
 */
function discoverModuleDirectories(string $root, array &$errors): array
{
    $modules = [];
    foreach (new FilesystemIterator($root) as $entry) {
        $name = $entry->getFilename();
        if (!$entry->isDir() || str_starts_with($name, '.')
            || in_array($name, ['libs', 'docs', 'imgs', 'tests', 'actions'], true)) {
            continue;
        }

        // Empty local leftovers cannot be shipped by Git and are not modules.
        if (!(new FilesystemIterator($entry->getPathname()))->valid()) {
            continue;
        }

        $modulePath = $entry->getPathname() . '/module.json';
        if (!is_file($modulePath)) {
            $errors[] = $name . '/module.json missing: non-exempt library root directories are treated as modules by Symcon.';
            continue;
        }
        $modules[$name] = $modulePath;
    }
    ksort($modules);

    return $modules;
}
