<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        /** @var array<string, string> */
        public array $properties = [];

        /** @var array<string, string> */
        public array $values = [];

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function ReadPropertyString(string $name): string
        {
            return $this->properties[$name] ?? '';
        }

        public function SetValue(string $ident, string $value): void
        {
            $this->values[$ident] = $value;
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveAdvTextfield/module.php';
require_once dirname(__DIR__) . '/SymconJSLiveCalendar/module.php';

function debugRequest(array $buffer): string
{
    return json_encode(['Buffer' => json_encode($buffer, JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR);
}

$text = new SymconJSLiveAdvTextfield();
$content = 'synthetic-private-content';
$result = $text->ReceiveData(debugRequest([
    'cmd'       => 'setData',
    'queryData' => ['val' => rawurlencode($content)]
]));
if ($result !== 'OK' || $text->values !== ['Content' => $content]) {
    throw new RuntimeException('AdvTextfield changed its successful write contract.');
}
if (str_contains(json_encode($text->debug, JSON_THROW_ON_ERROR), $content)) {
    throw new RuntimeException('AdvTextfield logged the written content.');
}

$text->debug = [];
if ($text->ReceiveData(debugRequest(['cmd' => 'setData', 'queryData' => []])) !== 'NO VALUE SET!'
    || $text->values !== ['Content' => $content]) {
    throw new RuntimeException('AdvTextfield changed its missing-value contract.');
}

$calendar = new SymconJSLiveCalendar();
$ics = "BEGIN:VCALENDAR\nEND:VCALENDAR";
$encodedIcs = base64_encode($ics);
$sourceName = 'synthetic-private-calendar-name';
$url = 'data://text/plain,BEGIN%3AVCALENDAR%0AEND%3AVCALENDAR?token=synthetic-secret';
$calendar->properties['dataEvents'] = json_encode([
    ['Name' => $sourceName, 'ical' => $encodedIcs, 'icalLink' => '', 'moduleInstance' => 0],
    ['Name' => 'Link', 'ical' => '', 'icalLink' => $url, 'moduleInstance' => 0]
], JSON_THROW_ON_ERROR);

$result = $calendar->ReceiveData(debugRequest(['cmd' => 'getICS', 'queryData' => ['md5' => md5($encodedIcs)]]));
if ($result !== $ics) {
    throw new RuntimeException('Calendar changed its embedded iCalendar response.');
}
if (str_contains(json_encode($calendar->debug, JSON_THROW_ON_ERROR), $sourceName)) {
    throw new RuntimeException('Calendar logged the embedded source name.');
}

$calendar->debug = [];
$result = $calendar->ReceiveData(debugRequest(['cmd' => 'getICS', 'queryData' => ['md5' => md5($url)]]));
if ($result !== file_get_contents($url)) {
    throw new RuntimeException('Calendar changed its linked iCalendar response.');
}
if (str_contains(json_encode($calendar->debug, JSON_THROW_ON_ERROR), 'synthetic-secret')) {
    throw new RuntimeException('Calendar logged a credential-bearing source URL.');
}

foreach ([$text, $calendar] as $module) {
    $module->debug = [];
    $result = $module->ReceiveData(debugRequest(['cmd' => 'unknown?password=synthetic-secret']));
    if ($result !== null || count($module->debug) !== 1) {
        throw new RuntimeException($module::class . ' changed its unknown-command handling.');
    }
    if (str_contains($module->debug[0]['data'], 'synthetic-secret')) {
        throw new RuntimeException($module::class . ' logged a request supplied command.');
    }
}

fwrite(STDOUT, "AdvTextfield and Calendar debug handling verified.\n");
