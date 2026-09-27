<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/helper/DebugHelper.php';

use Burki24\SymconModuleHelper\DebugHelper;

final class SplitterDebugHelperHarness
{
    use DebugHelper;

    /** @var list<array{message: string, data: string, format: int}> */
    public array $debug = [];

    public function send(string $message, mixed $data, array $additionalSensitiveKeys = []): void
    {
        $this->SendSafeDebug($message, $data, 16_384, $additionalSensitiveKeys);
    }

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debug[] = compact('message', 'data', 'format');
    }
}

function splitterDebugAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$source = file_get_contents(dirname(__DIR__) . '/SymconJSLive/module.php');
splitterDebugAssert($source !== false, 'JSLive splitter source must be readable.');
splitterDebugAssert(
    str_contains($source, "require_once dirname(__DIR__) . '/libs/helper/DebugHelper.php';"),
    'JSLive splitter must load the vendored DebugHelper.'
);
splitterDebugAssert(
    str_contains($source, 'use \\Burki24\\SymconModuleHelper\\DebugHelper;'),
    'JSLive splitter must use the DebugHelper trait.'
);
splitterDebugAssert(
    substr_count($source, '$this->SendDebug(') === 0,
    'JSLive splitter must not bypass DebugHelper with direct SendDebug() calls.'
);

$helper = new SplitterDebugHelperHarness();
$helper->send('WebHook', [
    'queryData' => [
        'instance' => '42',
        'pw'       => 'query-password'
    ],
    'post' => [
        'Password' => 'post-password'
    ],
    'contentLength' => 128
], ['pw']);

splitterDebugAssert(count($helper->debug) === 1, 'Splitter debug output must be forwarded once.');
$structured = json_decode($helper->debug[0]['data'], true, 512, JSON_THROW_ON_ERROR);
splitterDebugAssert(
    $structured['queryData']['pw'] === '***',
    'Webhook query passwords must be masked in splitter debug output.'
);
splitterDebugAssert(
    $structured['post']['Password'] === '***',
    'Webhook POST passwords must be masked in splitter debug output.'
);
splitterDebugAssert($structured['contentLength'] === 128, 'Safe diagnostic metadata must remain visible.');

fwrite(STDOUT, "JSLive splitter DebugHelper integration verified.\n");
