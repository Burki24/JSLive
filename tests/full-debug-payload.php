<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
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

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';

$module = new JSLiveModule();
$longText = str_repeat('L', 17_000);
$configuration = [
    'Title'    => 'Living room chart',
    'Password' => 'synthetic-config-password',
    'Nested'   => ['ApiKey' => 'synthetic-api-key']
];
$payload = [
    'cmd'       => 'unknown',
    'queryData' => [
        'title'       => 'Free text stays visible',
        'largeText'   => $longText,
        'pw'          => 'synthetic-browser-password',
        'jsonConfig'  => json_encode($configuration, JSON_THROW_ON_ERROR),
        'fileConfig'  => base64_encode(json_encode($configuration, JSON_THROW_ON_ERROR))
    ]
];
$request = json_encode(['Buffer' => json_encode($payload, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
$module->ReceiveData($request);
if (count($module->debug) !== 1 || $module->debug[0]['message'] !== 'ReceiveData') {
    throw new RuntimeException('JSLive child base must log each browser payload once with Debug enabled.');
}

$logged = json_decode($module->debug[0]['data'], true, 512, JSON_THROW_ON_ERROR);
$jsonConfiguration = json_decode($logged['queryData']['jsonConfig'], true, 512, JSON_THROW_ON_ERROR);
$fileConfiguration = json_decode(base64_decode($logged['queryData']['fileConfig'], true), true, 512, JSON_THROW_ON_ERROR);
if ($logged['queryData']['title'] !== 'Free text stays visible'
    || $logged['queryData']['largeText'] !== $longText
    || $jsonConfiguration['Title'] !== 'Living room chart'
    || $fileConfiguration['Title'] !== 'Living room chart') {
    throw new RuntimeException('JSLive debug shortened or omitted non-secret browser and configuration data.');
}
if ($logged['queryData']['pw'] !== '***'
    || $jsonConfiguration['Password'] !== '***'
    || $jsonConfiguration['Nested']['ApiKey'] !== '***'
    || $fileConfiguration['Password'] !== '***') {
    throw new RuntimeException('JSLive debug exposed known credentials.');
}

foreach ([
    'AdvTextfield', 'Chart', 'ColorPicker', 'Custom',
    'DateTimePicker', 'DoughnutPie', 'Gauge', 'Progressbar', 'RadarChart'
] as $name) {
    $source = file_get_contents(dirname(__DIR__) . '/SymconJSLive' . $name . '/module.php');
    if ($source === false || !str_contains($source, 'parent::ReceiveData($JSONString);')) {
        throw new RuntimeException($name . ' must pass browser requests through the common debug boundary.');
    }
}

$module->debug = [];
$module->debugEnabled = false;
$module->ReceiveData($request);
if ($module->debug !== []) {
    throw new RuntimeException('JSLive child base logged the browser payload with Debug disabled.');
}

fwrite(STDOUT, "JSLive full debug payload verified.\n");
