<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public function ReceiveData($JSONString)
        {
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function ReadPropertyString(string $name): string
        {
            if ($name === 'Datasets') {
                return '[{"Variable":42}]';
            }

            return '';
        }

        public function ReadPropertyInteger(string $name): int
        {
            return $name === 'Variable' ? 42 : 0;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

/** @var list<array{id: int, value: mixed}> */
$writes = [];

function RequestAction(int $id, mixed $value): void
{
    global $writes;
    $writes[] = compact('id', 'value');
}

function IPS_VariableExists(int $id): bool
{
    return $id === 42;
}

function IPS_GetVariable(int $id): array
{
    return ['VariableAction' => 1];
}

require_once dirname(__DIR__) . '/SymconJSLiveColorPicker/module.php';
require_once dirname(__DIR__) . '/SymconJSLiveDateTimePicker/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$color = new SymconJSLiveColorPicker();
$colorValue = 'synthetic-private-value';
$result = $color->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['var' => 42, 'val' => $colorValue]
]));
if ($result !== 'OK' || $writes !== [['id' => 42, 'value' => $colorValue]]) {
    throw new RuntimeException('ColorPicker changed its successful write contract.');
}
if (str_contains(json_encode($color->debug, JSON_THROW_ON_ERROR), $colorValue)) {
    throw new RuntimeException('ColorPicker logged the browser supplied value.');
}

$dateTime = new SymconJSLiveDateTimePicker();
$timestamp = 1700000000;
$result = $dateTime->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['var' => 42, 'val' => $timestamp]
]));
if ($result !== 'OK' || $writes[1] !== ['id' => 42, 'value' => $timestamp]) {
    throw new RuntimeException('DateTimePicker changed its successful write contract.');
}
if (str_contains(json_encode($dateTime->debug, JSON_THROW_ON_ERROR), (string) $timestamp)) {
    throw new RuntimeException('DateTimePicker logged the browser supplied timestamp.');
}

$writesBeforeDeniedRequests = $writes;
$result = $color->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['var' => 99, 'val' => 'synthetic-denied-color']
]));
if ($result !== 'VARIABLE NOT IN LIST SET!' || $writes !== $writesBeforeDeniedRequests) {
    throw new RuntimeException('ColorPicker accepted a variable outside its configured datasets.');
}

$result = $dateTime->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['var' => 99, 'val' => $timestamp]
]));
if ($result !== 'VARIABLE NOT SET!' || $writes !== $writesBeforeDeniedRequests) {
    throw new RuntimeException('DateTimePicker accepted a variable other than its configured target.');
}

foreach ([$color, $dateTime] as $module) {
    $module->debug = [];
    $result = $module->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
    if ($result !== null || count($module->debug) !== 1) {
        throw new RuntimeException($module::class . ' changed its unknown command handling.');
    }
    if (str_contains($module->debug[0]['data'], 'synthetic-secret')) {
        throw new RuntimeException($module::class . ' logged a request supplied command.');
    }
}

fwrite(STDOUT, "ColorPicker and DateTimePicker debug handling verified.\n");
