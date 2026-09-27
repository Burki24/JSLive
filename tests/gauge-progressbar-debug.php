<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        public function ReceiveData($JSONString)
        {
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveGauge/module.php';
require_once dirname(__DIR__) . '/SymconJSLiveProgressbar/module.php';

class GaugeDebugHarness extends SymconJSLiveGauge
{
    /** @var list<array{message: string, data: string, format: int}> */
    public array $debug = [];

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debug[] = compact('message', 'data', 'format');
    }
}

class ProgressbarDebugHarness extends SymconJSLiveProgressbar
{
    /** @var list<array{message: string, data: string, format: int}> */
    public array $debug = [];

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debug[] = compact('message', 'data', 'format');
    }
}

$secret = 'synthetic-secret-123';
$request = json_encode([
    'Buffer' => json_encode(['cmd' => 'unknown?password=' . $secret], JSON_THROW_ON_ERROR)
], JSON_THROW_ON_ERROR);

foreach ([new GaugeDebugHarness(), new ProgressbarDebugHarness()] as $module) {
    $result = $module->ReceiveData($request);
    if ($result !== null) {
        throw new RuntimeException($module::class . ' changed its unknown-command response.');
    }
    if (count($module->debug) !== 1) {
        throw new RuntimeException($module::class . ' must emit one diagnostic for an unknown command.');
    }
    $entry = $module->debug[0];
    if ($entry['message'] !== 'ReceiveData' || $entry['format'] !== 0) {
        throw new RuntimeException($module::class . ' changed its debug category or format.');
    }
    if (str_contains($entry['data'], $secret)) {
        throw new RuntimeException($module::class . ' exposed a request value in its debug output.');
    }
}

fwrite(STDOUT, "Gauge and Progressbar debug handling verified.\n");
