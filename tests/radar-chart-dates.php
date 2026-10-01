<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        protected $InstanceID;

        public function __construct(int $instanceID)
        {
            $this->InstanceID = $instanceID;
        }

        public function GetValue(string $ident): mixed
        {
            return $GLOBALS['jsliveRadarChartDateState'][$ident] ?? null;
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveRadarChart/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertRadarChartDate(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function invokeRadarChartDateMethod(
    SymconJSLiveRadarChart $module,
    string $method,
    array $arguments
): mixed {
    $reflection = new ReflectionMethod(SymconJSLiveRadarChart::class, $method);

    return $reflection->invokeArgs($module, $arguments);
}

function setRadarChartDateState(int $period, bool $relative): void
{
    $GLOBALS['jsliveRadarChartDateState'] = [
        'Period'  => $period,
        'Relativ' => $relative
    ];
}

date_default_timezone_set('UTC');

$module = new SymconJSLiveRadarChart(42);
$referenceTimestamp = (new DateTimeImmutable('2024-05-15 12:34:56 UTC'))->getTimestamp();

foreach ([false, true] as $relative) {
    foreach (range(0, 7) as $period) {
        setRadarChartDateState($period, $relative);
        $range = invokeRadarChartDateMethod($module, 'GetCorrectStartDate', [$referenceTimestamp]);

        assertRadarChartDate(
            is_array($range)
                && is_int($range['start'] ?? null)
                && is_int($range['end'] ?? null)
                && is_int($range['stufe'] ?? null)
                && ($range['start'] ?? 0) <= ($range['end'] ?? 0),
            sprintf(
                'Period %d (%s) must produce an ordered integer date range.',
                $period,
                $relative ? 'relative' : 'absolute'
            )
        );

        if (!$relative && $period >= 1) {
            $startYear = (new DateTimeImmutable('@' . $range['start']))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y');
            $endYear = (new DateTimeImmutable('@' . $range['end']))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y');
            assertRadarChartDate(
                $startYear === '2024' && $endYear === '2024',
                sprintf('Absolute period %d must use the reference timestamp year.', $period)
            );
        }

        $offsetRange = invokeRadarChartDateMethod(
            $module,
            'GetOffsetDate',
            [$range['start'], $range['end'], 1]
        );
        assertRadarChartDate(
            is_array($offsetRange)
                && is_int($offsetRange['start'] ?? null)
                && is_int($offsetRange['end'] ?? null)
                && is_int($offsetRange['interval'] ?? null)
                && ($offsetRange['start'] ?? 0) <= ($offsetRange['end'] ?? 0),
            sprintf(
                'Period %d (%s) must produce an ordered integer offset range.',
                $period,
                $relative ? 'relative' : 'absolute'
            )
        );
    }
}

setRadarChartDateState(5, false);
$day = invokeRadarChartDateMethod($module, 'GetCorrectStartDate', [$referenceTimestamp]);
$dayStart = (new DateTimeImmutable('@' . $day['start']))->setTimezone(new DateTimeZone('UTC'));
$dayEnd = (new DateTimeImmutable('@' . $day['end']))->setTimezone(new DateTimeZone('UTC'));
assertRadarChartDate(
    $dayStart->format('m-d H:i:s') === '05-15 00:00:00'
        && $dayEnd->format('m-d H:i:s') === '05-15 23:59:59',
    'An absolute day must retain its established month, day and UTC time boundaries.'
);

setRadarChartDateState(6, true);
$hour = invokeRadarChartDateMethod($module, 'GetCorrectStartDate', [$referenceTimestamp]);
assertRadarChartDate(
    $hour['start'] === (new DateTimeImmutable('2024-05-15 11:35:00 UTC'))->getTimestamp()
        && $hour['end'] === (new DateTimeImmutable('2024-05-15 12:34:00 UTC'))->getTimestamp(),
    'A relative hour must retain its established 60-minute boundaries.'
);

$customData = invokeRadarChartDateMethod(
    $module,
    'GetCustomData',
    [[0, 22.5, 45], ['22.5' => 7]]
);
assertRadarChartDate(
    $customData === [0, 7, 0],
    'Numeric RadarChart labels must remain compatible with strict string normalization.'
);

echo "JSLive RadarChart date range contracts verified.\n";
