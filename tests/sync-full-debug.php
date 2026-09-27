<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public bool $debugEnabled = true;

        public function ReadPropertyBoolean(string $name): bool
        {
            return $name === 'Debug' && $this->debugEnabled;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveSyncModule/module.php';

$module = new SymconJSLiveModuleSync();
$longText = str_repeat('C', 17_000);
$configuration = json_encode([
    'Title'    => $longText,
    'Password' => 'synthetic-sync-password'
], JSON_THROW_ON_ERROR);
$module->MessageSink(1700000000, 42, 999, [1, $configuration]);
if (count($module->debug) !== 1 || $module->debug[0]['message'] !== 'MessageSink') {
    throw new RuntimeException('SyncModule must log the incoming configuration with Debug enabled.');
}
$logged = json_decode($module->debug[0]['data'], true, 512, JSON_THROW_ON_ERROR);
$loggedConfig = json_decode($logged['data'][1], true, 512, JSON_THROW_ON_ERROR);
if ($loggedConfig['Title'] !== $longText || $loggedConfig['Password'] !== '***') {
    throw new RuntimeException('SyncModule shortened the configuration or exposed its password.');
}

$module->debug = [];
$module->debugEnabled = false;
$module->MessageSink(1700000000, 42, 999, [1, $configuration]);
if ($module->debug !== []) {
    throw new RuntimeException('SyncModule logged the configuration with Debug disabled.');
}

fwrite(STDOUT, "SyncModule full debug payload verified.\n");
