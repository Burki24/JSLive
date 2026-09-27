<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';

final class JSLiveModuleDebugHarness extends JSLiveModule
{
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

function jsliveModuleDebugAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$source = file_get_contents(dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php');
jsliveModuleDebugAssert($source !== false, 'JSLiveModule source must be readable.');
jsliveModuleDebugAssert(
    str_contains($source, "require_once dirname(__DIR__, 2) . '/libs/helper/DebugHelper.php';"),
    'JSLiveModule must load the vendored DebugHelper.'
);
jsliveModuleDebugAssert(
    str_contains($source, 'use \\Burki24\\SymconModuleHelper\\DebugHelper;'),
    'JSLiveModule must use the DebugHelper trait.'
);
jsliveModuleDebugAssert(
    substr_count($source, '$this->SendDebug(') === 0,
    'JSLiveModule must not bypass DebugHelper with direct SendDebug() calls.'
);

$helper = new JSLiveModuleDebugHarness();
$helper->send('Configuration', [
    'config' => [
        'Password'     => 'configuration-password',
        'ClientSecret' => 'configuration-secret',
        'Variable'     => 12345
    ],
    'data' => [
        'accessToken' => 'socket-token',
        'message'     => 10603
    ]
]);

jsliveModuleDebugAssert(count($helper->debug) === 1, 'JSLiveModule debug output must be forwarded once.');
$structured = json_decode($helper->debug[0]['data'], true, 512, JSON_THROW_ON_ERROR);
jsliveModuleDebugAssert(
    $structured['config']['Password'] === '***',
    'Configuration passwords must be masked.'
);
jsliveModuleDebugAssert(
    $structured['config']['ClientSecret'] === '***',
    'Configuration client secrets must be masked.'
);
jsliveModuleDebugAssert(
    $structured['data']['accessToken'] === '***',
    'Socket access tokens must be masked.'
);
jsliveModuleDebugAssert($structured['config']['Variable'] === 12345, 'Safe configuration metadata must remain visible.');

fwrite(STDOUT, "JSLiveModule DebugHelper integration verified.\n");
