<?php

declare(strict_types=1);

set_error_handler(static function (int $severity, string $message): bool
{ if (!(error_reporting() & $severity)) { return false; } throw new RuntimeException($message); });

// Isolated read-only Symcon boundary: any write is a test failure.
class IPSModuleStrict
{
    public function __construct(protected int $InstanceID)
    {
    }
    public function SendDataToParent(string $json): string
    {
        return json_encode(['DataMode' => 1, 'RefreshTime' => 3, 'EnableViewport' => true, 'CreateIPSView' => false, 'Password' => 'must-never-leak', 'Address' => 'must-never-leak']);
    }
    public function ReadPropertyString(string $name): string
    {
        return $GLOBALS['exportConfig'][$name] ?? '';
    }
    public function ReadPropertyInteger(string $name): int
    {
        return $GLOBALS['exportConfig'][$name] ?? 0;
    }
    public function ReadPropertyFloat(string $name): float
    {
        return $GLOBALS['exportConfig'][$name] ?? 0.0;
    }
    public function ReadPropertyBoolean(string $name): bool
    {
        return $GLOBALS['exportConfig'][$name] ?? false;
    }
    public function SendDebug(string $message, string $data, int $format): void
    {
    }
    protected function GetValue(string $ident): mixed
    {
        return GetValue(IPS_GetObjectIDByIdent($ident, $this->InstanceID));
    }
}
function IPS_GetInstance(int $id): array
{
    return ['ModuleInfo' => ['ModuleID' => '{11111111-1111-4111-8111-111111111111}', 'ModuleName' => $GLOBALS['exportModule']]];
}
function IPS_GetObject(int $id): array
{
    return ['ObjectName' => $id === 12000 ? 'Synthetic chart' : 'Repeated name'];
}
function IPS_GetConfiguration(int $id): string
{
    return json_encode($GLOBALS['exportConfig'], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
}
function IPS_GetObjectIDByIdent(string $ident, int $id): int
{
    return ['Period' => 500, 'Now' => 501, 'Relativ' => 502, 'Offset' => 503, 'StartDate' => 504][$ident];
}
function GetValue(int $id): mixed
{
    return [500 => 5, 501 => true, 502 => true, 503 => 0, 504 => 0][$id] ?? 21.5;
}
function IPS_VariableExists(int $id): bool
{
    return in_array($id, [13000, 13001, 500, 501, 502, 503, 504], true);
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => 2, 'VariableProfile' => '', 'VariableCustomProfile' => '', 'VariableAction' => 0];
}
function IPS_GetVariablePresentation(int $id): array
{
    return ['PRESENTATION' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}', 'SUFFIX' => ' °C', 'DIGITS' => 1];
}
function IPS_VariableProfileExists(string $name): bool
{
    return $name === 'Example.Temperature';
}
function IPS_GetVariableProfile(string $name): array
{
    return ['ProfileName' => $name, 'ProfileType' => 2, 'MinValue' => -20.0, 'MaxValue' => 60.0, 'StepSize' => 0.1, 'Digits' => 1, 'Prefix' => '', 'Suffix' => ' °C', 'Icon' => '', 'Associations' => []];
}
function IPS_GetInstanceListByModuleID(string $id): array
{
    return [2];
}
function SymconJSLive_GetMigrationInstallationID(int $id): string
{
    return '11111111-1111-4111-8111-111111111111';
}
function IPS_GetKernelVersion(): string
{
    return '9.1';
}
function AC_GetAggregationType(int $archive, int $id): int
{
    return 0;
}
function AC_GetLoggingStatus(int $archive, int $id): bool
{
    return true;
}
function IPS_ScriptExists(int $id): bool
{
    return $id === 14000;
}
function IPS_GetScriptContent(int $id): string
{
    return '<script>mustNeverExecute()</script>';
}
function IPS_SetConfiguration(int $id, string $config): void
{
    throw new RuntimeException('Unexpected configuration write.');
}
function SetValue(int $id, mixed $value): void
{
    throw new RuntimeException('Unexpected value write.');
}
function AC_SetLoggingStatus(int $a, int $id, bool $value): void
{
    throw new RuntimeException('Unexpected archive write.');
}

foreach (['Chart', 'DoughnutPie', 'Gauge', 'RadarChart', 'Progressbar'] as $family) {
    require_once __DIR__ . '/../SymconJSLive' . $family . '/module.php';
}
function assertEChartsExport(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
$base = ['TemplateScriptID' => 0, 'title_text' => 'Synthetic chart', 'title_display' => true,
    'data_sections'         => 16, 'data_minValue' => 0, 'data_maxValue' => 360, 'data_precision' => 2, 'data_highResSteps' => 1, 'Axes' => '[]', 'Ticks' => '[]', 'Highlights' => '[]',
    'customScale'           => '[]', 'LastUploadedConfig' => 'must-not-export', 'Debug' => false];
foreach (['Chart', 'DoughnutPie', 'Gauge', 'RadarChart', 'Progressbar'] as $family) {
    $GLOBALS['exportModule'] = 'SymconJSLive' . $family;
    $GLOBALS['exportConfig'] = $base;
    if (in_array($family, ['Gauge', 'Progressbar'], true)) {
        $GLOBALS['exportConfig'] += ['Variable' => 13000, 'min' => 0.0, 'max' => 100.0, 'precision' => 0, 'data_min' => 0.0, 'data_max' => 100.0];
    } elseif ($family === 'DoughnutPie') {
        $GLOBALS['exportConfig']['Datasets'] = json_encode([['Order' => 0, 'Profile' => 'Example.Temperature', 'Variables' => [['Variable' => 13000, 'Label' => 'A'], ['Variable' => 13001, 'Label' => 'A']]]]);
    } else {
        $GLOBALS['exportConfig']['Axes'] = json_encode([['Ident' => 't', 'Profile' => 'Example.Temperature', 'Bool' => false, 'DynScale' => false, 'OverrideMinMax' => false, 'OverrideStepSize' => 0, 'AxesStackOffset' => false, 'AxesStackGroup' => 0, 'Side' => 'left', 'Title' => 'Temperature', 'beginAtZero' => -1]]);
        $GLOBALS['exportConfig']['Datasets'] = json_encode([['Variable' => 13000, 'Type' => 'line', 'Axes' => 't', 'Offset' => 0, 'HighRes' => 7], ['Variable' => 13000, 'Type' => 'line', 'Axes' => 't', 'Offset' => 1, 'HighRes' => 7]]);
    }
    $module = new ($GLOBALS['exportModule'])(12000);
    $before = $GLOBALS['exportConfig'];
    $package = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
    assertEChartsExport($package['issues'] === [], 'Unexpected export problems: ' . json_encode($package['issues']));
    assertEChartsExport(!str_contains(json_encode($package), 'must-never-leak') && $package['charts'][0]['original']['globalConfiguration']['CreateIPSView'] === false, 'Parent secrets leaked or explicit rendering flags lost.');
    assertEChartsExport($package['formatVersion'] === '1.0', 'Version missing.');
    assertEChartsExport($GLOBALS['exportConfig'] === $before, 'Export changed configuration.');
    $chart = $package['charts'][0];
    assertEChartsExport(!isset($chart['original']['configuration']['LastUploadedConfig']), 'Recursive import history leaked.');
    assertEChartsExport($chart['original']['configuration']['title_text'] === 'Synthetic chart', 'Title omitted like filtered legacy export.');
    assertEChartsExport(count($chart['bindings']) >= 1 && $chart['sources'][0]['variableId'] === 13000, 'Source binding missing.');
    assertEChartsExport($chart['sources'][0]['presentation']['native']['parameters']['SUFFIX'] === ' °C', 'Native source presentation missing.');
    if (in_array($family, ['Chart', 'RadarChart'], true)) {
        assertEChartsExport(count($chart['sources']) === 1 && count($chart['bindings']) === 2, 'Repeated binding was collapsed or source duplicated.');
        assertEChartsExport($chart['original']['runtimeState']['Relativ']['value'] === true, 'Time controls missing.');
        assertEChartsExport($chart['time']['resolvedWindow']['untilExclusive'] > $chart['time']['resolvedWindow']['fromInclusive'], 'Window missing.');
    }
    $GLOBALS['exportConfig']['TemplateScriptID'] = 14000;
    $without = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
    $with = json_decode($module->ExportECharts(true), true, 32, JSON_THROW_ON_ERROR);
    assertEChartsExport(count($without['assets']) === 1 && count($with['assets']) === 2, 'Script opt-in was not respected.');
    assertEChartsExport(base64_decode($with['assets'][1]['contentBase64']) === IPS_GetScriptContent(14000), 'Template not retained byte-identically.');
    assertEChartsExport($with['assets'][1]['sha256'] === hash('sha256', IPS_GetScriptContent(14000)), 'Resource integrity missing.');
}
$GLOBALS['exportConfig']['TemplateScriptID'] = 0;
$GLOBALS['exportConfig']['title_fontFamily'] = 'Roboto';
$fontPackage = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
assertEChartsExport($fontPackage['issues'] === [] && count($fontPackage['assets']) >= 3, 'Local font dependencies were not fully bundled.');
$GLOBALS['exportConfig']['Variable'] = 0;
$missing = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
assertEChartsExport(in_array('SOURCE_UNRESOLVED', array_column($missing['issues'], 'code'), true), 'Missing source silently accepted.');
$GLOBALS['exportConfig']['Variable'] = 13000;
$GLOBALS['exportConfig']['title_fontFamily'] = '../../outside';
$unsafe = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
assertEChartsExport(in_array('ASSET_UNAVAILABLE', array_column($unsafe['issues'], 'code'), true), 'Unsafe font path was accepted.');
$GLOBALS['exportConfig']['title_fontFamily'] = '';
$GLOBALS['exportConfig']['LastUploadedConfig'] = str_repeat('old-import', 70000);
$clean = json_decode($module->ExportECharts(), true, 32, JSON_THROW_ON_ERROR);
assertEChartsExport(!isset($clean['charts'][0]['original']['configuration']['LastUploadedConfig']), 'Operational import history consumed the configuration budget.');
$GLOBALS['exportConfig']['title_text'] = str_repeat('X', 524289);
try {
    $module->ExportECharts();
    throw new LogicException('Oversized chart accepted.');
}
catch (RuntimeException $exception) {
    assertEChartsExport(str_contains($exception->getMessage(), 'LIMIT_EXCEEDED'), 'Unexpected limit error.');
}
echo "Five real JSLive export entry points, controls, repeated bindings, profile snapshots, resources and read-only behavior verified.\n";
