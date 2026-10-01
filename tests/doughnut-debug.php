<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public function ReadPropertyString(string $name): string
        {
            if ($name === 'Datasets') {
                return '[{"Variables":[{"Variable":42},{"Variable":42}]}]';
            }

            return '';
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return $name === 'Debug';
        }

        public function ReadPropertyInteger(string $name): int
        {
            return 0;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

function IPS_VariableExists(int $id): bool
{
    return $id === 42;
}

function GetValue(int $id): int
{
    return $id === 42 ? 5 : 0;
}

require_once dirname(__DIR__) . '/SymconJSLiveDoughnutPie/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$module = new SymconJSLiveDoughnutPie();
$allResult = $module->ReceiveData(debugRequest([
    'instance'  => 1,
    'cmd'       => 'getData',
    'queryData' => []
]));
if (json_decode($allResult, true, 512, JSON_THROW_ON_ERROR) !== [['Variable' => 42, 'Value' => 5]]) {
    throw new RuntimeException('DoughnutPie must return a configured variable only once.');
}

$result = $module->ReceiveData(debugRequest([
    'instance'  => 1,
    'cmd'       => 'getData',
    'queryData' => ['var' => 42, 'password' => 'synthetic-secret']
]));
if (json_decode($result, true, 512, JSON_THROW_ON_ERROR) !== [['Variable' => 42, 'Value' => 5]]) {
    throw new RuntimeException('DoughnutPie changed its getData response.');
}
if (str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-secret')) {
    throw new RuntimeException('DoughnutPie logged the browser supplied query.');
}

$module->debug = [];
$result = $module->ReceiveData(debugRequest(['instance' => 1, 'cmd' => 'unknown?password=synthetic-secret']));
if ($result !== '' || count($module->debug) !== 2) {
    throw new RuntimeException('DoughnutPie changed its unknown-command handling.');
}
if (str_contains($module->debug[0]['data'], 'synthetic-secret')) {
    throw new RuntimeException('DoughnutPie logged a request supplied command.');
}

fwrite(STDOUT, "DoughnutPie debug handling verified.\n");
