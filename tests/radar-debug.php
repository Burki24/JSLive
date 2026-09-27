<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public bool $debugEnabled = true;

        public function ReadPropertyInteger(string $name): int
        {
            return match ($name) {
                'data_minValue'  => 0,
                'data_maxValue'  => 20000,
                'data_sections'  => 2,
                'data_precision' => 0,
                default          => 0
            };
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return $name === 'Debug' && $this->debugEnabled;
        }

        public function ReadPropertyString(string $name): string
        {
            return $name === 'customScale' ? '[]' : '';
        }

        public function GetValue(string $ident): int
        {
            return $ident === 'Period' ? 6 : 0;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

function IPS_GetInstanceListByModuleID(string $moduleID): array
{
    return [99];
}

function IPS_VariableExists(int $id): bool
{
    return $id === 42;
}

function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 1];
}

function AC_GetLoggedValues(int $archiveID, int $variableID, int $start, int $end, int $limit): array
{
    return $GLOBALS['radarArchiveValues'] ?? [['TimeStamp' => 1700000000, 'Value' => $GLOBALS['radarArchiveValue']]];
}

require_once dirname(__DIR__) . '/SymconJSLiveRadarChart/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$module = new SymconJSLiveRadarChart();
$GLOBALS['radarArchiveValue'] = 12345;
$result = (new ReflectionMethod(SymconJSLiveRadarChart::class, 'GetArchivData'))->invoke(
    $module,
    [0, 10000],
    42,
    0,
    0,
    1700000000,
    1700000060,
    0,
    'average'
);
if ($result !== [0, 12345.0]) {
    throw new RuntimeException('RadarChart changed its archive data response.');
}
$debug = json_encode($module->debug, JSON_THROW_ON_ERROR);
if (!str_contains($debug, '12345') || str_contains($debug, 'OUPUT =>')
    || str_contains($debug, '[0,12345]')) {
    throw new RuntimeException('RadarChart must log a numeric sample, not the complete result series.');
}

$module->debug = [];
$module->debugEnabled = false;
$GLOBALS['radarArchiveValue'] = 30000;
$result = (new ReflectionMethod(SymconJSLiveRadarChart::class, 'GetArchivData'))->invoke(
    $module,
    [0, 10000],
    42,
    0,
    0,
    1700000000,
    1700000060,
    0,
    'average'
);
if ($result !== [0, 0] || $module->debug !== []) {
    throw new RuntimeException('RadarChart logged an out-of-range archive value with Debug disabled.');
}

$module->debugEnabled = true;
$module->debug = [];
$GLOBALS['radarArchiveValues'] = array_map(static fn (int $index): array => [
    'TimeStamp' => 1700000000 + $index,
    'Value'     => 12000 + $index
], range(0, 10));
(new ReflectionMethod(SymconJSLiveRadarChart::class, 'GetArchivData'))->invoke(
    $module,
    [0, 10000],
    42,
    0,
    0,
    1700000000,
    1700000060,
    0,
    'average'
);
$sampleEntries = array_filter($module->debug, static fn (array $entry): bool => str_contains($entry['data'], '"sample"'));
if (count($sampleEntries) !== 11 || !str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), '12010')) {
    throw new RuntimeException('RadarChart must log every numeric archive value.');
}

$module->debug = [];
$GLOBALS['radarArchiveValues'] = array_map(static fn (int $index): array => [
    'TimeStamp' => 1700000000 + $index,
    'Value'     => 30000 + $index
], range(0, 10));
$result = (new ReflectionMethod(SymconJSLiveRadarChart::class, 'GetArchivData'))->invoke(
    $module,
    [0, 10000],
    42,
    0,
    0,
    1700000000,
    1700000060,
    0,
    'average'
);
if ($result !== [0, 0] || !str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), '30010')) {
    throw new RuntimeException('RadarChart must log numeric out-of-range values without changing its result.');
}
unset($GLOBALS['radarArchiveValues']);

$module->debug = [];
$result = (new ReflectionMethod(SymconJSLiveRadarChart::class, 'GetCustomData'))->invoke(
    $module,
    ['known'],
    ['synthetic-private-key' => 7]
);
if (!is_array($result) || count($result) !== 1) {
    throw new RuntimeException('RadarChart changed its custom-data response shape.');
}
if (str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-private-key')) {
    throw new RuntimeException('RadarChart logged a custom data key.');
}

$module->debug = [];
$result = $module->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
if ($result !== null || count($module->debug) !== 2) {
    throw new RuntimeException('RadarChart changed its unknown-command handling.');
}
if (str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-secret')) {
    throw new RuntimeException('RadarChart logged a request supplied command.');
}

fwrite(STDOUT, "RadarChart debug handling verified.\n");
