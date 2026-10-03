<?php

declare(strict_types=1);

// Only the Symcon boundary is simulated; rendering uses the real Gauge module.
class IPSModuleStrict
{
    protected int $InstanceID = 42;

    public function ReadPropertyBoolean(string $name): bool
    {
        return (bool) ($GLOBALS['gaugeRendering'][$name] ?? false);
    }

    public function ReadPropertyInteger(string $name): int
    {
        return (int) ($GLOBALS['gaugeRendering'][$name] ?? 0);
    }

    public function ReadPropertyFloat(string $name): float
    {
        return (float) ($GLOBALS['gaugeRendering'][$name] ?? 0);
    }

    public function ReadPropertyString(string $name): string
    {
        return (string) ($GLOBALS['gaugeRendering'][$name] ?? '');
    }
}

function IPS_GetConfiguration(int $id): string
{
    return json_encode($GLOBALS['gaugeRendering'], JSON_THROW_ON_ERROR);
}

function IPS_VariableExists(int $id): bool
{
    return $id === 4201;
}

function GetValue(int $id): float
{
    return 12.345;
}

function IPS_ScriptExists(int $id): bool
{
    return $id === 9001;
}

function IPS_GetScriptContent(int $id): string
{
    return '<title>{TITLE_TEXT}</title>{FONTS}<script>let configuration = {CONFIG};'
        . 'let config_ticks = {TICKS};let config_highlights = {HIGHLIGHTS};'
        . 'let value = {VALUE};</script>{GLOBAL}{INSTANCE}{PASSWORD}{VIEWPORT}';
}

require_once dirname(__DIR__) . '/SymconJSLiveGauge/module.php';

final class GaugeRenderingHarness extends SymconJSLiveGauge
{
    public function renderWebpage(): string
    {
        return $this->GetWebpage();
    }
}

function assertGaugeRendering(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$module = new GaugeRenderingHarness();
$cases = 0;
// Explicit expectations: measurement precision must never determine opacity.
$alphas = [[0.25, '0.25'], [0.75, '0.75'], [0.01, '0.01'], [0.99, '0.99'], [0.0, '0.00'], [1.0, '1.00']];
$bounds = [
    0 => ['10', '20', '12'],
    1 => ['10.3', '20.3', '12.3'],
    2 => ['10.25', '20.25', '12.35'],
    3 => ['10.250', '20.250', '12.345']
];
foreach (['CanvasGauges-Radial', 'CanvasGauges-Linear', 'CanvasGauges-Linear(vertical)', 'CanvasGauges-Compass', 'custom'] as $template) {
    foreach ($bounds as $precision => [$from, $to, $value]) {
        foreach ($alphas as [$alpha, $expectedAlpha]) {
            $GLOBALS['gaugeRendering'] = [
                'template'         => $template, 'TemplateScriptID' => $template === 'custom' ? 9001 : 0,
                'Variable'         => 4201, 'precision' => $precision, 'min' => 0.0, 'max' => 100.0,
                'title_text'       => 'Synthetic Gauge', 'plate_display' => true,
                'plate_colorPlate' => 0x112233, 'plate_colorPlate_Alpha' => $alpha,
                'Ticks'            => '[]',
                'Highlights'       => json_encode([
                    ['From' => 30.0, 'To' => 40.0, 'HighlightColor' => 0xAABBCC, 'HighlightColor_Alpha' => 1.0],
                    ['From' => 10.25, 'To' => 20.25, 'HighlightColor' => 0x112233, 'HighlightColor_Alpha' => $alpha]
                ], JSON_THROW_ON_ERROR)
            ];
            $before = $GLOBALS['gaugeRendering'];
            $html = $module->renderWebpage();
            $context = $template . ', precision=' . $precision . ', alpha=' . $alpha;
            assertGaugeRendering(preg_match('/let config_highlights = (\[.*?\]);/', $html, $matches) === 1, 'Missing highlights: ' . $context);
            $highlights = $matches[1];
            $expected = '{from : ' . $from . ', to : ' . $to . ', color : "rgba(17, 34, 51, ' . $expectedAlpha . ')"}';
            assertGaugeRendering(str_starts_with($highlights, '[' . $expected . ', '), 'Highlight bounds/order/opacity changed: ' . $context . '; actual=' . $highlights);
            assertGaugeRendering(str_contains($highlights, 'rgba(170, 187, 204, 1.00)'), 'Opaque second highlight changed: ' . $context);
            assertGaugeRendering(str_contains($html, 'plate_colorPlate : "rgba(17, 34, 51, ' . $expectedAlpha . ')"'), 'Plate and highlight opacity must use the same independent precision: ' . $context);
            assertGaugeRendering($GLOBALS['gaugeRendering'] === $before, 'Rendering must not alter saved configuration.');
            if ($template === 'custom') {
                assertGaugeRendering(str_contains($html, 'let value = ' . $value . ';'), 'Measurement precision changed: ' . $context);
            }
            $cases++;
        }
    }
}

echo 'Gauge highlight rendering verified (' . $cases . " cases).\n";
