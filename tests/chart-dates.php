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
            return $GLOBALS['jsliveChartDateState'][$ident] ?? null;
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function ReadPropertyInteger(string $name): int
        {
            return 0;
        }

        public function ReadPropertyString(string $name): string
        {
            return $name === 'Datasets' ? '[]' : '';
        }

        public function GetBuffer(string $name): string
        {
            return '[]';
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }
    }
}

if (!function_exists('IPS_VariableExists')) {
    function IPS_VariableExists(int $variableID): bool
    {
        return false;
    }
}

if (!function_exists('IPS_GetInstanceListByModuleID')) {
    function IPS_GetInstanceListByModuleID(string $moduleID): array
    {
        return [1];
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveChart/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertChartDate(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function invokeChartDateMethod(
    SymconJSLiveChart $module,
    string $method,
    array $arguments
): mixed {
    $reflection = new ReflectionMethod(SymconJSLiveChart::class, $method);

    return $reflection->invokeArgs($module, $arguments);
}

function setChartDateState(
    int $period,
    bool $relative,
    bool $now,
    int $startDate
): void {
    $GLOBALS['jsliveChartDateState'] = [
        'Period'    => $period,
        'Relativ'   => $relative,
        'Now'       => $now,
        'StartDate' => $startDate
    ];
}

date_default_timezone_set('UTC');

$module = new SymconJSLiveChart(42);
$referenceTimestamp = (new DateTimeImmutable('2024-05-15 12:34:56 UTC'))->getTimestamp();

foreach ([false, true] as $relative) {
    foreach (range(0, 7) as $period) {
        setChartDateState($period, $relative, true, $referenceTimestamp);
        $range = invokeChartDateMethod($module, 'GetCorrectStartDate', [$referenceTimestamp]);

        assertChartDate(
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
            assertChartDate(
                $startYear === '2024' && $endYear === '2024',
                sprintf('Absolute period %d must use the reference timestamp year.', $period)
            );
        }

        $offsetRange = invokeChartDateMethod(
            $module,
            'GetOffsetDate',
            [$range['start'], $range['end'], 1]
        );
        assertChartDate(
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

setChartDateState(5, false, false, $referenceTimestamp);
$configuredDay = invokeChartDateMethod($module, 'GetCorrectStartDate', []);
$configuredStart = (new DateTimeImmutable('@' . $configuredDay['start']))->setTimezone(new DateTimeZone('UTC'));
$configuredEnd = (new DateTimeImmutable('@' . $configuredDay['end']))->setTimezone(new DateTimeZone('UTC'));
assertChartDate(
    $configuredStart->format('m-d H:i:s') === '05-15 00:00:00'
        && $configuredEnd->format('m-d H:i:s') === '05-15 23:59:59',
    'A configured absolute day must retain its established month, day and UTC time boundaries.'
);

setChartDateState(6, true, true, $referenceTimestamp);
$hour = invokeChartDateMethod($module, 'GetCorrectStartDate', [$referenceTimestamp]);
assertChartDate(
    $hour['start'] === (new DateTimeImmutable('2024-05-15 11:35:00 UTC'))->getTimestamp()
        && $hour['end'] === (new DateTimeImmutable('2024-05-15 12:34:00 UTC'))->getTimestamp(),
    'A relative hour must retain its established 60-minute boundaries.'
);

$datasetUpdate = json_decode($module->GetUpdate(['id' => '0']), true);
assertChartDate(
    $datasetUpdate === ['DATASETS' => []],
    'A numeric dataset index from a web query must remain compatible with strict typing.'
);

$variableData = json_decode(
    invokeChartDateMethod($module, 'GetData', [['var' => '42']]),
    true
);
assertChartDate(
    $variableData === [],
    'A numeric variable ID from a web query must remain compatible with strict typing.'
);

echo "JSLive Chart date range contracts verified.\n";
