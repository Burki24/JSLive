<?php

declare(strict_types=1);

// Kernel boundaries only: exercise the real module's public data-flow endpoint.
class IPSModuleStrict
{
    protected int $InstanceID = 100;
    public function ReceiveData(string $JSONString): string
    {
        return '';
    }
    public function ReadPropertyString(string $name): string
    {
        return $name === 'Datasets' ? json_encode($GLOBALS['datasets'], JSON_THROW_ON_ERROR) : '[]';
    }
    public function ReadPropertyInteger(string $name): int
    {
        return 0;
    }
    public function ReadPropertyBoolean(string $name): bool
    {
        return false;
    }
    public function SendDebug(string $message, string $data, int $format): void
    {
    }
    public function Translate(string $text): string
    {
        return $text;
    }
}

$datasets = [['Variable' => 101, 'Mode' => 'xy'], ['Variable' => 102, 'Mode' => 'xy'],
    ['Variable' => 103, 'Mode' => 'color'], ['Variable'       => 104, 'Mode' => 'kelvin'], ['Variable' => 105, 'Mode' => 'mired']];
$values = [101 => '{"x":0.64,"y":0.33}', 102 => '{"x":0.3,"y":0.6}', 103 => 255, 104 => 4000, 105 => 250];
$rgb = [101 => 0xFF0000, 102 => 0x00FF00];
$writes = [];
$reads = [];
$wrongPresentation = false;
$actionResult = true;
$nativeWarning = false;
$nativeFalse = false;
$warnings = [];
$warningHandler = static function (int $severity, string $message) use (&$warnings): bool
{
    $warnings[] = $message;
    return true;
};
set_error_handler($warningHandler, E_WARNING);
function IPS_VariableExists(int $id): bool
{
    return array_key_exists($id, $GLOBALS['values']);
}
function GetValue(int $id): mixed
{
    return $GLOBALS['values'][$id];
}
function IPS_GetConfiguration(int $id): string
{
    return '{"Datasets":"[]","Layout":"[]","style_borderColor":0}';
}
function IPS_GetVariable(int $id): array
{
    return ['VariableType' => is_string(GetValue($id)) ? 3 : 1];
}
function IPS_GetVariablePresentation(int $id): array
{
    return ['PRESENTATION' => '{05CC3CC2-A0B2-5837-A4A7-A07EA0B9DDFB}', 'ENCODING' => $GLOBALS['wrongPresentation'] ? 0 : 4];
}
function GetValueColor(int $id): int|false
{
    $GLOBALS['reads'][] = $id;
    if (GetValue($id) === '' || ($id === 101 && $GLOBALS['nativeWarning'])) {
        // Native Symcon failures emit E_WARNING and return false, not exceptions.
        fopen('php://jslive-invalid-color-stream', 'rb');
        return false;
    }
    if ($id === 101 && $GLOBALS['nativeFalse']) return false;
    return $GLOBALS['rgb'][$id];
}
function RequestActionColor(int $id, int $value): bool
{
    $GLOBALS['writes'][] = ['xy', $id, $value];
    return $GLOBALS['actionResult'];
}
function RequestAction(int $id, mixed $value): bool
{
    $GLOBALS['writes'][] = ['legacy', $id, $value];
    return true;
}
require_once dirname(__DIR__) . '/SymconJSLiveColorPicker/module.php';
class RenderableColorPicker extends SymconJSLiveColorPicker
{
    public function Render(): string
    {
        return $this->GetWebpage();
    }
}
$module = new RenderableColorPicker();
function request(string $command, array $query = []): string
{
    return $GLOBALS['module']->ReceiveData(json_encode(['Buffer' => json_encode(['cmd' => $command, 'queryData' => $query], JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR));
}
function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
check(($data[0]['ColorValue'] ?? null) === 0xFF0000 && ($data[1]['ColorValue'] ?? null) === 0x00FF00, 'xy variables must be read through native color conversion.');
check($data[0]['Value'] === $values[101] && $data[0]['Mode'] === 'xy', 'Raw values and selected mode remain intact.');
check($reads === [101, 102] && $writes === [], 'Reading must not write or convert legacy modes.');
check($data[2] === ['Variable' => 103, 'Value' => 255, 'Mode' => 'color'], 'Legacy wire shape stays unchanged.');
preg_match('/let config_variabels = (.*);/', $module->Render(), $embedded);
$initial = json_decode($embedded[1], true, 512, JSON_THROW_ON_ERROR);
check($initial === $data, 'Initial HTML must embed the same raw xy JSON and RGB projection safely.');
check(request('setData', ['var' => '101', 'val' => '16711680']) === 'OK', 'First xy lamp accepts RGB input.');
check(request('setData', ['var' => '102', 'val' => '255']) === 'OK', 'Second xy lamp is independently addressed.');
check($writes === [['xy', 101, 0xFF0000], ['xy', 102, 255]], 'Only the selected lamp action may run.');
foreach (['-1', '16777216', '1.5', '1e2', 'abc', '{"x":0.3,"y":0.3}', [], null] as $invalid) {
    check(request('setData', ['var' => 101, 'val' => $invalid]) !== 'OK', 'Malformed RGB input must fail.');
}
check(request('setData', ['var' => 999, 'val' => '255']) !== 'OK' && count($writes) === 2, 'Unconfigured targets and invalid values must never reach an action.');
$actionResult = false;
check(request('setData', ['var' => 101, 'val' => 255]) !== 'OK', 'Failed device action must not be reported as success.');
$wrongPresentation = true;
$before = count($writes);
check(request('setData', ['var' => 101, 'val' => 255]) !== 'OK' && count($writes) === $before, 'An incompatible presentation must not be written.');
$wrongPresentation = false;
$values[101] = '';
$data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
check($data[0]['ColorValue'] === null && $data[0]['Error'] !== '' && $data[1]['ColorValue'] === 0x00FF00, 'One unreadable xy value must not break other handles.');
check($warnings === [], 'An empty xy value must not emit a native warning into the module response.');
preg_match('/let config_variabels = (.*);/', $module->Render(), $embedded);
check(json_decode($embedded[1], true, 512, JSON_THROW_ON_ERROR)[0]['ColorValue'] === null, 'HTML must preserve null for a disabled xy handle.');
$values[101] = '</script><script>throw new Error("unsafe")</script>';
check(!str_contains($module->Render(), $values[101]), 'Variable text must not escape the inline script element.');
$invalidValues = ['', '{', '{}', 'null', '[]', '[0.3,0.3]', '"xy"', '{"x":0.3}',
    '{"x":null,"y":null}', '{"x":"bad","y":0.3}', '{"x":"0.3","y":0.3}',
    '{"x":true,"y":0.3}', '{"x":[],"y":0.3}', '{"x":{},"y":0.3}',
    '{"x":-0.1,"y":0.3}', '{"x":1.1,"y":0.3}', '{"x":0.3,"y":-0.1}',
    '{"x":0.3,"y":1.1}', '{"x":1e999,"y":0.3}', '{"x":0.3,"y":1e999}'];
$writesBeforeReads = $writes;
foreach ($invalidValues as $raw) {
    $values[101] = $raw;
    $reads = [];
    $data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
    check($reads === [102], 'Invalid xy data must be rejected before native conversion: ' . $raw);
    check($data[0]['Value'] === $raw && $data[0]['ColorValue'] === null && $data[0]['Error'] !== '', 'The invalid handle retains its raw value and reports an error.');
    check(count($data) === 5 && $data[1]['ColorValue'] === 0x00FF00 && $data[2]['Value'] === 255, 'Other xy and RGB handles remain readable.');
    preg_match('/let config_variabels = (.*);/', $module->Render(), $embedded);
    check(json_decode($embedded[1], true, 512, JSON_THROW_ON_ERROR) === $data, 'Initial HTML and polling must isolate the same invalid handle.');
}
foreach (['{"x":0,"y":1}', '{"x":1,"y":0}', '{"x":0.3127,"y":0.329}', '{"x":3e-1,"y":6e-1}'] as $raw) {
    $values[101] = $raw;
    $reads = [];
    $data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
    check($reads === [101, 102] && $data[0]['Error'] === '' && is_int($data[0]['ColorValue']), 'Valid numeric xy coordinates recover without reconfiguration.');
}
// Cover a changed value/presentation between prevalidation and the native read.
$nativeWarning = true;
$data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
check($data[0]['ColorValue'] === null && $data[0]['Error'] !== '' && $data[1]['Error'] === '', 'A native warning is isolated to its handle.');
check($warnings === [], 'Native warnings must not escape into the module response.');
$restored = set_error_handler($warningHandler);
check($restored === $warningHandler, 'The original error handler must be restored after native failure.');
restore_error_handler();
$nativeWarning = false;
$nativeFalse = true;
$data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
check($data[0]['ColorValue'] === null && $data[0]['Error'] !== '', 'A false native result remains a read failure.');
$nativeFalse = false;
$data = json_decode(request('getData'), true, 512, JSON_THROW_ON_ERROR);
check($data[0]['ColorValue'] === 0xFF0000 && $data[0]['Error'] === '', 'Native warning recovery clears the error.');
$restored = set_error_handler($warningHandler);
check($restored === $warningHandler && $writes === $writesBeforeReads, 'Successful reads restore the handler and never write.');
restore_error_handler();
foreach ([103 => '65535', 104 => '4500', 105 => '300'] as $id => $value) {
    check(request('setData', ['var' => $id, 'val' => $value]) === 'OK' && end($writes) === ['legacy', $id, $value], 'Legacy writes keep their action contract.');
}
echo "ColorPicker xy data flow, target isolation, invalid inputs and legacy modes verified.\n";
restore_error_handler();
