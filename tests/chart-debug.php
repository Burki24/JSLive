<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public bool $debugEnabled = true;
        protected int $InstanceID = 42;

        public function ReadPropertyInteger(string $name): int
        {
            return $name === 'data_highResSteps' ? 1 : 0;
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return $name === 'Debug' && $this->debugEnabled;
        }

        public function ReadPropertyString(string $name): string
        {
            return match ($name) {
                'Datasets' => '[{"Variable":42,"Axes":""}]',
                'Axes'     => '[]',
                default    => ''
            };
        }

        public function GetBuffer(string $name): string
        {
            return '[]';
        }

        public function GetValue(string $ident): int|bool
        {
            return $ident === 'Period' ? 7 : false;
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
    return 345;
}

function IPS_GetInstanceListByModuleID(string $moduleID): array
{
    return [99];
}

function IPS_GetConfiguration(int $instanceID): string
{
    return json_encode(['title_text' => 'Synthetic chart'], JSON_THROW_ON_ERROR);
}

function IPS_GetInstance(int $instanceID): array
{
    return ['ModuleInfo' => ['ModuleID' => '{SYNTHETIC-CHART-MODULE}']];
}

function AC_GetAggregationType(int $archiveID, int $variableID): int
{
    return 0;
}

function AC_GetLoggedValues(int $archiveID, int $variableID, int $start, int $end, int $limit): array
{
    return $GLOBALS['chartArchiveValues'];
}

require_once dirname(__DIR__) . '/SymconJSLiveChart/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$module = new SymconJSLiveChart();
$configuration = json_decode(
    $module->ReceiveData(debugRequest(['cmd' => 'getConfiguration', 'queryData' => []])),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$language = json_decode(
    $module->ReceiveData(debugRequest(['cmd' => 'getLanguage', 'queryData' => []])),
    true,
    512,
    JSON_THROW_ON_ERROR
);
if ($language !== $configuration) {
    throw new RuntimeException('Chart language route must retain the configuration response expected by its loader.');
}

$result = json_decode($module->ReceiveData(debugRequest([
    'cmd' => 'getData', 'queryData' => ['var' => 42, 'password' => 'synthetic-secret']
])), true, 512, JSON_THROW_ON_ERROR);
if ($result !== [['Variable' => 42, 'Value' => 345]]) {
    throw new RuntimeException('Chart changed its current-value response.');
}
if (str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-secret')
    || !str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), '345')) {
    throw new RuntimeException('Chart must log the numeric value without exposing the browser supplied query.');
}

$GLOBALS['chartArchiveValues'] = array_map(static fn (int $index): array => [
    'TimeStamp' => 1700000000 + $index,
    'Value'     => 12000 + $index
], range(0, 10));
$module->debug = [];
$result = (new ReflectionMethod(SymconJSLiveChart::class, 'GetArchivData'))->invoke(
    $module,
    42,
    7,
    0,
    1700000000,
    1700000060,
    0
);
if (count($result) !== 11 || $result[0]['y'] !== 12000.0 || $result[10]['y'] !== 12010.0) {
    throw new RuntimeException('Chart changed its archive data response.');
}
$debug = json_encode($module->debug, JSON_THROW_ON_ERROR);
$valueEntries = array_filter($module->debug, static fn (array $entry): bool => str_contains($entry['data'], '"value"'));
if (count($valueEntries) !== 11 || !str_contains($debug, '12000') || !str_contains($debug, '12010') || str_contains($debug, 'OUTPUT [')) {
    throw new RuntimeException('Chart must log every numeric archive value without exposing the complete result series.');
}

$module->debug = [];
$result = (new ReflectionMethod(SymconJSLiveChart::class, 'GetArchivData'))->invoke(
    $module,
    42,
    7,
    0,
    1700000000,
    1700000060,
    0,
    0,
    ['synthetic-on-label', 'synthetic-off-label']
);
if ($result[0]['y'] !== 'synthetic-off-label'
    || str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-off-label')) {
    throw new RuntimeException('Chart changed or exposed configured text labels.');
}

$module->debug = [];
$module->debugEnabled = false;
(new ReflectionMethod(SymconJSLiveChart::class, 'GetArchivData'))->invoke(
    $module,
    42,
    7,
    0,
    1700000000,
    1700000060,
    0
);
if ($module->debug !== []) {
    throw new RuntimeException('Chart logged archive data with Debug disabled.');
}

$module->debugEnabled = true;
$module->debug = [];
$result = $module->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
if ($result !== '' || count($module->debug) !== 2
    || str_contains(json_encode($module->debug, JSON_THROW_ON_ERROR), 'synthetic-secret')) {
    throw new RuntimeException('Chart changed or exposed its unknown-command handling.');
}

fwrite(STDOUT, "Chart debug handling verified.\n");
