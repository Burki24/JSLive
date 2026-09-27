<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/helper/DebugHelper.php';

use Burki24\SymconModuleHelper\DebugHelper;

final class SyncDebugHelperHarness
{
    use DebugHelper;

    /** @var list<array{message: string, data: string, format: int}> */
    public array $debug = [];

    public function send(string $message, mixed $data): void
    {
        $this->SendSafeDebug($message, $data);
    }

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debug[] = compact('message', 'data', 'format');
    }
}

function syncDebugAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$helper = new SyncDebugHelperHarness();
$helper->send('Synchronization', [
    'Password' => 'secret-password',
    'ApiKey'   => 'secret-api-key',
    'config'   => [
        'ClientSecret' => 'secret-client-secret',
        'instance'     => 42
    ]
]);

syncDebugAssert(count($helper->debug) === 1, 'SyncModule debug output must be forwarded once.');
syncDebugAssert($helper->debug[0]['format'] === 0, 'SyncModule debug output must use text format 0.');

$structured = json_decode($helper->debug[0]['data'], true, 512, JSON_THROW_ON_ERROR);
syncDebugAssert($structured['Password'] === '***', 'Passwords must be masked in SyncModule debug output.');
syncDebugAssert($structured['ApiKey'] === '***', 'API keys must be masked in SyncModule debug output.');
syncDebugAssert(
    $structured['config']['ClientSecret'] === '***',
    'Nested client secrets must be masked in SyncModule debug output.'
);
syncDebugAssert($structured['config']['instance'] === 42, 'Non-sensitive values must remain visible.');

$source = file_get_contents(dirname(__DIR__) . '/SymconJSLiveSyncModule/module.php');
syncDebugAssert($source !== false, 'SyncModule source must be readable.');
syncDebugAssert(
    str_contains($source, "require_once dirname(__DIR__) . '/libs/helper/DebugHelper.php';"),
    'SyncModule must load the vendored DebugHelper.'
);
syncDebugAssert(
    str_contains($source, 'use \\Burki24\\SymconModuleHelper\\DebugHelper;'),
    'SyncModule must use the DebugHelper trait.'
);
syncDebugAssert(
    substr_count($source, '$this->SendDebug(') === 0,
    'SyncModule must not bypass DebugHelper with direct SendDebug() calls.'
);

fwrite(STDOUT, "SyncModule DebugHelper integration verified.\n");
