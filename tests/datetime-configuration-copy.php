<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        protected int $InstanceID;

        public function __construct(int $instanceID = 42)
        {
            $this->InstanceID = $instanceID;
        }
    }
}

const JSLIVE_DATETIME_MODULE_ID = '{A1111111-1111-1111-1111-111111111111}';

$GLOBALS['jsliveDateTimeCopyState'] = [
    'sourceConfiguration'  => [
        'Variable'              => 999,
        'Template'              => 'TimePicker2',
        'style_backgroundColor' => 1_234_567
    ],
    'writtenInstance'      => null,
    'writtenConfiguration' => null,
    'appliedInstances'     => []
];

if (!function_exists('IPS_ObjectExists')) {
    function IPS_ObjectExists(int $objectID): bool
    {
        return $objectID === 99;
    }
}

if (!function_exists('IPS_GetObject')) {
    function IPS_GetObject(int $objectID): array
    {
        return ['ObjectType' => 1];
    }
}

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return ['ModuleInfo' => ['ModuleID' => JSLIVE_DATETIME_MODULE_ID]];
    }
}

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode(
            $GLOBALS['jsliveDateTimeCopyState']['sourceConfiguration'],
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_SetConfiguration')) {
    function IPS_SetConfiguration(int $instanceID, string $configuration): void
    {
        $GLOBALS['jsliveDateTimeCopyState']['writtenInstance'] = $instanceID;
        $GLOBALS['jsliveDateTimeCopyState']['writtenConfiguration'] = json_decode(
            $configuration,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_ApplyChanges')) {
    function IPS_ApplyChanges(int $instanceID): void
    {
        $GLOBALS['jsliveDateTimeCopyState']['appliedInstances'][] = $instanceID;
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveDateTimePicker/module.php';

final class DateTimeConfigurationCopyHarness extends SymconJSLiveDateTimePicker
{
    /** @var list<string> */
    public array $stringPropertyReads = [];

    public function ReadPropertyBoolean(string $name): bool
    {
        return false;
    }

    public function ReadPropertyInteger(string $name): int
    {
        return $name === 'Variable' ? 123 : 0;
    }

    public function ReadPropertyString(string $name): string
    {
        $this->stringPropertyReads[] = $name;

        return '';
    }
}

function assertDateTimeConfigurationCopy(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$module = new DateTimeConfigurationCopyHarness();
$result = $module->LoadOtherConfiguration(99);
$writtenConfiguration = $GLOBALS['jsliveDateTimeCopyState']['writtenConfiguration'];

assertDateTimeConfigurationCopy($result === null, 'A valid configuration copy must keep its empty success response.');
assertDateTimeConfigurationCopy(
    $module->stringPropertyReads === [],
    'The DateTimePicker must not read an unregistered string property while copying configuration.'
);
assertDateTimeConfigurationCopy(
    ($writtenConfiguration['Variable'] ?? null) === 123,
    'The target DateTimePicker variable binding must be preserved.'
);
assertDateTimeConfigurationCopy(
    !array_key_exists('Variables', $writtenConfiguration),
    'The copied configuration must not contain the unregistered Variables property.'
);
assertDateTimeConfigurationCopy(
    ($writtenConfiguration['Template'] ?? null) === 'TimePicker2'
        && ($writtenConfiguration['style_backgroundColor'] ?? null) === 1_234_567,
    'The remaining source configuration must still be copied.'
);
assertDateTimeConfigurationCopy(
    $GLOBALS['jsliveDateTimeCopyState']['writtenInstance'] === 42
        && $GLOBALS['jsliveDateTimeCopyState']['appliedInstances'] === [42],
    'A valid configuration copy must update and apply the target exactly once.'
);

fwrite(STDOUT, "DateTimePicker configuration copy verified.\n");
