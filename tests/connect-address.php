<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        protected int $InstanceID;

        public function __construct(int $instanceID)
        {
            $this->InstanceID = $instanceID;
        }
    }
}

const JSLIVE_CONNECT_CONTROL_GUID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';

$GLOBALS['jsliveConnectAddressState'] = [
    'instanceLists'        => [],
    'requestedModules'     => [],
    'connectUrls'          => [],
    'requestedInstances'   => [],
    'configuration'        => ['Address' => '', 'RefreshTime' => 3],
    'writtenConfiguration' => null,
    'appliedInstances'     => []
];

if (!function_exists('IPS_GetInstanceListByModuleID')) {
    function IPS_GetInstanceListByModuleID(string $moduleID): array
    {
        $GLOBALS['jsliveConnectAddressState']['requestedModules'][] = $moduleID;

        return $GLOBALS['jsliveConnectAddressState']['instanceLists'][$moduleID] ?? [];
    }
}

if (!function_exists('CC_GetUrl')) {
    function CC_GetUrl(int $instanceID): string
    {
        $GLOBALS['jsliveConnectAddressState']['requestedInstances'][] = $instanceID;

        return $GLOBALS['jsliveConnectAddressState']['connectUrls'][$instanceID] ?? '';
    }
}

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode(
            $GLOBALS['jsliveConnectAddressState']['configuration'],
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_SetConfiguration')) {
    function IPS_SetConfiguration(int $instanceID, string $configuration): void
    {
        $GLOBALS['jsliveConnectAddressState']['writtenConfiguration'] = json_decode(
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
        $GLOBALS['jsliveConnectAddressState']['appliedInstances'][] = $instanceID;
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/module.php';

final class ConnectAddressHarness extends SymconJSLive
{
    private string $address = '';

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function ReadPropertyString(string $name): string
    {
        return $name === 'Address' ? $this->address : '';
    }
}

function assertConnectAddress(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function resetConnectAddressCalls(): void
{
    $GLOBALS['jsliveConnectAddressState']['requestedModules'] = [];
    $GLOBALS['jsliveConnectAddressState']['requestedInstances'] = [];
    $GLOBALS['jsliveConnectAddressState']['writtenConfiguration'] = null;
    $GLOBALS['jsliveConnectAddressState']['appliedInstances'] = [];
}

$connectInstanceID = 7654;
$connectUrl = 'https://synthetic.ipmagic.de';
$GLOBALS['jsliveConnectAddressState']['instanceLists'] = [
    JSLIVE_CONNECT_CONTROL_GUID => [$connectInstanceID]
];
$GLOBALS['jsliveConnectAddressState']['connectUrls'] = [
    $connectInstanceID => $connectUrl
];

$module = new ConnectAddressHarness(42);
$module->setAddress('http://configured.example');

$result = $module->LoadConnectAddress(false);
assertConnectAddress($result === $connectUrl, 'The form callback must return the current Connect URL.');
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['requestedModules'] === [JSLIVE_CONNECT_CONTROL_GUID],
    'Connect discovery must query the Connect Control module GUID.'
);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['requestedInstances'] === [$connectInstanceID],
    'CC_GetUrl must receive the discovered Connect Control instance ID.'
);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['writtenConfiguration'] === null,
    'Reading the Connect URL must not modify the splitter configuration.'
);

resetConnectAddressCalls();
$module->LoadConnectAddress(true);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['requestedModules'] === [],
    'Startup must keep an already configured address without querying Connect Control.'
);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['writtenConfiguration'] === null,
    'Startup must not overwrite an already configured address.'
);

resetConnectAddressCalls();
$module->setAddress('');
$module->LoadConnectAddress(true);
assertConnectAddress(
    ($GLOBALS['jsliveConnectAddressState']['writtenConfiguration']['Address'] ?? null) === $connectUrl,
    'Startup must populate an empty address with the discovered Connect URL.'
);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['appliedInstances'] === [42],
    'A populated startup address must be applied exactly once.'
);

resetConnectAddressCalls();
$GLOBALS['jsliveConnectAddressState']['instanceLists'] = [];
assertConnectAddress(
    $module->LoadConnectAddress(false) === '',
    'A missing Connect Control instance must produce an empty URL.'
);
$module->LoadConnectAddress(true);
assertConnectAddress(
    $GLOBALS['jsliveConnectAddressState']['writtenConfiguration'] === null
        && $GLOBALS['jsliveConnectAddressState']['appliedInstances'] === [],
    'Startup must not write or apply an empty Connect URL.'
);

fwrite(STDOUT, "Connect address behavior verified.\n");
