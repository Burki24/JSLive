<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public function ReadPropertyString(string $name): string
        {
            if ($name === 'Datasets') {
                return json_encode(array_map(static fn (int $id): array => [
                    'Object'   => $id,
                    'ReadOnly' => false
                ], [42, 43, 44, 45]), JSON_THROW_ON_ERROR);
            }
            if ($name === 'Libraries') {
                return json_encode([[
                    'Ident'  => 'embedded',
                    'Script' => 0,
                    'File'   => base64_encode('synthetic-private-library'),
                    'Type'   => 'js'
                ]], JSON_THROW_ON_ERROR);
            }

            return '';
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

/** @var list<array{id: int, value: mixed}> */
$writes = [];

function IPS_ObjectExists(int $id): bool
{
    return in_array($id, [42, 43, 44, 45], true);
}

function IPS_GetObject(int $id): array
{
    return [
        'ObjectType'       => [42 => 2, 43 => 3, 44 => 5, 45 => 6][$id],
        'ObjectName'       => 'Synthetic object',
        'ObjectIsHidden'   => false,
        'ObjectIsDisabled' => false,
        'HasChildren'      => false,
        'ChildrenIDs'      => []
    ];
}

function IPS_GetVariable(int $id): array
{
    return [
        'VariableChanged'       => 0,
        'VariableUpdated'       => 0,
        'VariableType'          => 3,
        'VariableProfile'       => '',
        'VariableCustomProfile' => ''
    ];
}

function IPS_VariableProfileExists(string $name): bool
{
    return false;
}

function GetValue(int $id): string
{
    return 'synthetic-private-read';
}

function SetValue(int $id, mixed $value): void
{
    global $writes;
    $writes[] = ['id' => $id, 'value' => $value];
}

function IPS_RunScriptWaitEx(int $id, array $parameters): string
{
    global $writes;
    $writes[] = ['id' => $id, 'value' => $parameters];
    return 'script result';
}

function IPS_SetMediaContent(int $id, string $content): void
{
    global $writes;
    $writes[] = ['id' => $id, 'value' => $content];
}

function IPS_GetLink(int $id): array
{
    return ['TargetID' => 42];
}

require_once dirname(__DIR__) . '/SymconJSLiveCustom/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

function assertNoCustomSecret(SymconJSLiveCustom $module, string $secret): void
{
    if (str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), $secret)) {
        throw new RuntimeException('Custom logged a private value: ' . $secret);
    }
}

$module = new SymconJSLiveCustom();

$result = json_decode($module->ReceiveData(debugRequest([
    'cmd' => 'loadFile', 'queryData' => ['ident' => 'embedded']
])), true, 512, JSON_THROW_ON_ERROR);
if ($result !== ['Contend' => 'synthetic-private-library', 'Type' => 'js']) {
    throw new RuntimeException('Custom changed its embedded library response.');
}
assertNoCustomSecret($module, base64_encode('synthetic-private-library'));

$module->debug = [];
$result = json_decode($module->ReceiveData(debugRequest([
    'cmd' => 'loadFile', 'queryData' => ['ident' => 'synthetic-private-ident']
])), true, 512, JSON_THROW_ON_ERROR);
if ($result !== ['Contend' => '', 'Type' => '']) {
    throw new RuntimeException('Custom changed its unknown library response.');
}
assertNoCustomSecret($module, 'synthetic-private-ident');

$module->debug = [];
$result = $module->ReceiveData(debugRequest([
    'cmd' => 'setData', 'queryData' => ['obj' => 42, 'val' => 'synthetic-private-write']
]));
if ($result !== 'OK' || $writes !== [['id' => 42, 'value' => 'synthetic-private-write']]) {
    throw new RuntimeException('Custom changed its variable write contract.');
}
assertNoCustomSecret($module, 'synthetic-private-write');

$module->debug = [];
$scriptValue = '{"secret":"synthetic-private-script"}';
$result = $module->ReceiveData(debugRequest([
    'cmd' => 'setData', 'queryData' => ['obj' => 43, 'val' => $scriptValue]
]));
if ($result !== 'script result' || $writes[1] !== ['id' => 43, 'value' => ['secret' => 'synthetic-private-script']]) {
    throw new RuntimeException('Custom changed its script call contract.');
}
assertNoCustomSecret($module, 'synthetic-private-script');

$module->debug = [];
$result = $module->ReceiveData(debugRequest([
    'cmd' => 'setData', 'queryData' => ['obj' => 44, 'val' => 'synthetic-private-media']
]));
if ($result !== 'OK' || $writes[2] !== ['id' => 44, 'value' => 'synthetic-private-media']) {
    throw new RuntimeException('Custom changed its media write contract.');
}
assertNoCustomSecret($module, 'synthetic-private-media');

$module->debug = [];
$result = $module->ReceiveData(debugRequest([
    'cmd' => 'setData', 'queryData' => ['obj' => 45, 'val' => 'synthetic-private-link-write']
]));
if ($result !== 'OK' || $writes[3] !== ['id' => 42, 'value' => 'synthetic-private-link-write']) {
    throw new RuntimeException('Custom changed its linked variable write contract.');
}
assertNoCustomSecret($module, 'synthetic-private-link-write');

$module->debug = [];
$result = (new ReflectionMethod(SymconJSLiveCustom::class, 'LoadDataFromObject'))->invoke($module, 42);
if (($result['Value'] ?? null) !== 'synthetic-private-read') {
    throw new RuntimeException('Custom changed its object read contract.');
}
assertNoCustomSecret($module, 'synthetic-private-read');

$module->debug = [];
$result = $module->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
if ($result !== null || count($module->debug) !== 1) {
    throw new RuntimeException('Custom changed its unknown-command handling.');
}
assertNoCustomSecret($module, 'synthetic-secret');

fwrite(STDOUT, "Custom debug handling verified.\n");
