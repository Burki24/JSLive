<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
    }
}

$testDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jslive-debug-log-' . bin2hex(random_bytes(8));

if (!mkdir($testDirectory)) {
    throw new RuntimeException('Unable to create temporary log directory.');
}

$GLOBALS['jsliveDebugLogDirectory'] = $testDirectory . DIRECTORY_SEPARATOR;

if (!function_exists('IPS_GetLogDir')) {
    function IPS_GetLogDir(): string
    {
        return $GLOBALS['jsliveDebugLogDirectory'];
    }
}

require_once __DIR__ . '/../SymconJSLive/libs/JSLiveModule.php';

$logFile = $testDirectory . DIRECTORY_SEPARATOR . 'logfile.log';
$harmlessLine = '2026-10-01 12:00:00 | Temperature updated to 21.5 C';
$password = 'synthetic-password';
$shortPassword = 'synthetic-short-password';
$jsonPassword = 'synthetic-json-password';

try {
    $logContent = implode(PHP_EOL, [
        $harmlessLine,
        'Login failed: password=' . $password,
        'Webhook: /hook/JSLive/?instance=123&PW=' . $shortPassword . '&view=chart',
        json_encode(['Password' => $jsonPassword, 'event' => 'login'], JSON_THROW_ON_ERROR)
    ]) . PHP_EOL;

    if (file_put_contents($logFile, $logContent) === false) {
        throw new RuntimeException('Unable to create synthetic Symcon log file.');
    }

    $module = new JSLiveModule();
    ob_start();

    try {
        $module->Debug_LoadLogFile(12345);
        $output = (string) ob_get_clean();
    } catch (Throwable $throwable) {
        ob_end_clean();
        throw $throwable;
    }

    if (!str_contains($output, $harmlessLine)) {
        throw new RuntimeException('Debug_LoadLogFile must keep sending non-sensitive log content.');
    }

    if (!str_contains($output, '&view=chart')) {
        throw new RuntimeException('Debug_LoadLogFile must preserve non-sensitive query parameters.');
    }

    if (!str_contains($output, 'PW=***')) {
        throw new RuntimeException('Debug_LoadLogFile must preserve the password parameter name while masking its value.');
    }

    foreach ([$password, $shortPassword, $jsonPassword] as $secret) {
        if (str_contains($output, $secret)) {
            throw new RuntimeException('Debug_LoadLogFile must not expose password values.');
        }
    }

    if (substr_count($output, '***') < 3) {
        throw new RuntimeException('Debug_LoadLogFile must visibly mask every known password form.');
    }
} finally {
    if (is_file($logFile)) {
        unlink($logFile);
    }

    if (is_dir($testDirectory)) {
        rmdir($testDirectory);
    }

    unset($GLOBALS['jsliveDebugLogDirectory']);
}

fwrite(STDOUT, "Debug log password masking verified.\n");
