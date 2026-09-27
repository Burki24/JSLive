<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        protected $InstanceID;

        public function __construct(int $instanceID)
        {
            $this->InstanceID = $instanceID;
        }

        public function ReadPropertyString(string $name): string
        {
            return (string) ($GLOBALS['jsliveCustomDataState']['properties'][$name] ?? '');
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }
    }
}

if (!function_exists('IPS_ObjectExists')) {
    function IPS_ObjectExists(int $objectID): bool
    {
        return false;
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveCustom/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertCustomData(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function invokeCustomDataMethod(
    SymconJSLiveCustom $module,
    string $method,
    array $arguments = []
): mixed {
    $reflection = new ReflectionMethod(SymconJSLiveCustom::class, $method);

    return $reflection->invokeArgs($module, $arguments);
}

$GLOBALS['jsliveCustomDataState'] = [
    'properties' => [
        'Datasets' => json_encode([
            [
                'Title'                 => 'Example',
                'HighlightColor1'       => 0x112233,
                'HighlightColor1_Alpha' => '0.5',
                'HighlightColor2'       => -1,
                'HighlightColor2_Alpha' => '1',
                'Object'                => 0
            ]
        ], JSON_THROW_ON_ERROR)
    ]
];

$module = new SymconJSLiveCustom(42);
$allData = json_decode(invokeCustomDataMethod($module, 'GetAllData'), true);
assertCustomData(
    ($allData[0]['HighlightColor1'] ?? null) === 'rgba(17, 34, 51, 0.50)'
        && ($allData[0]['HighlightColor2'] ?? null) === 'rgba(0,0,0,0)',
    'Numeric-string alpha values must retain their established RGBA rendering.'
);

$missingObject = invokeCustomDataMethod(
    $module,
    'SetData',
    [['obj' => '42', 'val' => '1']]
);
assertCustomData(
    $missingObject === 'OBJECT NOT EXIST!',
    'A numeric object ID from a web query must remain compatible with strict typing.'
);

echo "JSLive Custom data contracts verified.\n";
