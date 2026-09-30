<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        /** @var array<string, string> */
        public array $values = [];

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function SetValue(string $ident, string $value): void
        {
            $this->values[$ident] = $value;
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveAdvTextfield/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$text = new SymconJSLiveAdvTextfield();
$content = 'synthetic%20private content';
$result = $text->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['val' => $content]
]));
if ($result !== 'OK' || $text->values !== ['Content' => $content]) {
    throw new RuntimeException('AdvTextfield changed its successful write contract.');
}
if (str_contains(json_encode($text->debug, JSON_THROW_ON_ERROR), $content)) {
    throw new RuntimeException('AdvTextfield logged the written content.');
}

$text->debug = [];
if ($text->ReceiveData(debugRequest(['cmd' => 'setData', 'queryData' => []])) !== 'NO VALUE SET!'
    || $text->values !== ['Content' => $content]) {
    throw new RuntimeException('AdvTextfield changed its missing-value contract.');
}

$text->debug = [];
$result = $text->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
if ($result !== null || count($text->debug) !== 1) {
    throw new RuntimeException('AdvTextfield changed its unknown-command handling.');
}
if (str_contains($text->debug[0]['data'], 'synthetic-secret')) {
    throw new RuntimeException('AdvTextfield logged a request supplied command.');
}

fwrite(STDOUT, "AdvTextfield debug handling verified.\n");
