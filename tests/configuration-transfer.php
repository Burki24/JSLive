<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        protected $InstanceID;

        public function __construct($instanceID)
        {
            $this->InstanceID = $instanceID;
        }
    }
}

$GLOBALS['jsliveConfigTransferState'] = [
    'configuration'     => [],
    'configurationForm' => [],
    'instances'         => [],
    'setConfiguration'  => [],
    'applyChanges'      => []
];

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return $GLOBALS['jsliveConfigTransferState']['instances'][$instanceID];
    }
}

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode(
            $GLOBALS['jsliveConfigTransferState']['configuration'][$instanceID],
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_GetConfigurationForm')) {
    function IPS_GetConfigurationForm(int $instanceID): string
    {
        return json_encode(
            $GLOBALS['jsliveConfigTransferState']['configurationForm'][$instanceID],
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_ScriptExists')) {
    function IPS_ScriptExists(int $scriptID): bool
    {
        return false;
    }
}

if (!function_exists('IPS_SetConfiguration')) {
    function IPS_SetConfiguration(int $instanceID, string $configuration): void
    {
        $GLOBALS['jsliveConfigTransferState']['setConfiguration'][] = [
            'instanceID'    => $instanceID,
            'configuration' => $configuration
        ];
    }
}

if (!function_exists('IPS_ApplyChanges')) {
    function IPS_ApplyChanges(int $instanceID): void
    {
        $GLOBALS['jsliveConfigTransferState']['applyChanges'][] = $instanceID;
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';

final class ConfigurationTransferHarness extends JSLiveModule
{
    /** @var list<array{message: string, data: string}> */
    public array $debugMessages = [];

    public function __construct()
    {
        parent::__construct(42);
    }

    public function ReadPropertyInteger(string $name): int
    {
        return 0;
    }

    public function ReadPropertyBoolean(string $name): bool
    {
        return false;
    }

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debugMessages[] = ['message' => $message, 'data' => $data];
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertConfigurationTransfer(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function resetConfigurationTransferMutations(): void
{
    $GLOBALS['jsliveConfigTransferState']['setConfiguration'] = [];
    $GLOBALS['jsliveConfigTransferState']['applyChanges'] = [];
}

/** @return array<string, mixed> */
function decodeExportedConfiguration(string $json): array
{
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    assertConfigurationTransfer(is_array($data), 'The exported configuration must be a JSON object.');

    return $data;
}

$state = &$GLOBALS['jsliveConfigTransferState'];
$state['instances'][42] = [
    'ModuleInfo' => [
        'ModuleID'   => '{11111111-2222-3333-4444-555555555555}',
        'ModuleName' => 'Synthetic JSLive Module'
    ]
];
$state['configuration'][42] = [
    'Visible'            => 'fixture-value',
    'Ignored'            => 'synthetic-private-placeholder',
    'TemplateScriptID'   => 0,
    'LastUploadedConfig' => ''
];
$state['configurationForm'][42] = [
    'elements' => [
        ['type' => 'ValidationTextBox', 'name' => 'Visible'],
        ['type' => 'ValidationTextBox', 'name' => 'Ignored', 'ignoreExport' => true]
    ],
    'actions' => []
];

$harness = new ConfigurationTransferHarness();

$completeExport = decodeExportedConfiguration($harness->ExportConfiguration(true));
assertConfigurationTransfer(
    ($completeExport['ModuleID'] ?? null) === '{11111111-2222-3333-4444-555555555555}',
    'The export changed the module identifier.'
);
assertConfigurationTransfer(
    ($completeExport['ModuleName'] ?? null) === 'Synthetic JSLive Module',
    'The export changed the module name.'
);
assertConfigurationTransfer(
    ($completeExport['Config'] ?? null) === $state['configuration'][42],
    'A complete export must preserve the configuration.'
);

$filteredExport = decodeExportedConfiguration($harness->ExportConfiguration(false));
assertConfigurationTransfer(
    ($filteredExport['Config']['Visible'] ?? null) === 'fixture-value',
    'A regular export must preserve allowed properties.'
);
assertConfigurationTransfer(
    !array_key_exists('Ignored', $filteredExport['Config']),
    'A regular export must omit fields marked with ignoreExport.'
);

$allowList = $harness->GetAllowConfigurationExportList([
    [
        'type'    => 'List',
        'name'    => 'Datasets',
        'columns' => [
            ['name' => 'Variable'],
            ['name' => 'Internal', 'ignoreExport' => true]
        ]
    ]
]);
assertConfigurationTransfer(
    ($allowList['Datasets_Variable'] ?? null) === ['is_column' => true, 'ignore' => false],
    'List-column export metadata changed for allowed values.'
);
assertConfigurationTransfer(
    ($allowList['Datasets_Internal'] ?? null) === ['is_column' => true, 'ignore' => true],
    'List-column export metadata changed for ignored values.'
);

resetConfigurationTransferMutations();
assertConfigurationTransfer(
    $harness->LoadConfigurationFile('') === 'File is Empty!',
    'An empty import must be rejected.'
);
assertConfigurationTransfer(
    $state['setConfiguration'] === [] && $state['applyChanges'] === [],
    'An empty import must not change the instance.'
);

resetConfigurationTransferMutations();
assertConfigurationTransfer(
    $harness->LoadConfigurationFile(base64_encode('not-json')) === 'Not valid json File!(1)',
    'Invalid JSON must be rejected.'
);
assertConfigurationTransfer(
    $state['setConfiguration'] === [] && $state['applyChanges'] === [],
    'Invalid JSON must not change the instance.'
);

resetConfigurationTransferMutations();
$missingMetadata = base64_encode(json_encode(['Config' => []], JSON_THROW_ON_ERROR));
assertConfigurationTransfer(
    $harness->LoadConfigurationFile($missingMetadata) === 'Not valid json File!(2)',
    'An import without module metadata must be rejected.'
);
assertConfigurationTransfer(
    $state['setConfiguration'] === [] && $state['applyChanges'] === [],
    'An import without module metadata must not change the instance.'
);

resetConfigurationTransferMutations();
$wrongModule = base64_encode(json_encode(
    [
        'ModuleID'   => '{AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE}',
        'ModuleName' => 'Other Synthetic Module',
        'Config'     => ['Visible' => 'other-value']
    ],
    JSON_THROW_ON_ERROR
));
assertConfigurationTransfer(
    $harness->LoadConfigurationFile($wrongModule) === 'Configuration only allowed for Other Synthetic Module',
    'An import for a different module must be rejected.'
);
assertConfigurationTransfer(
    $state['setConfiguration'] === [] && $state['applyChanges'] === [],
    'An import for a different module must not change the instance.'
);

resetConfigurationTransferMutations();
$validImport = base64_encode(json_encode(
    [
        'ModuleID'   => '{11111111-2222-3333-4444-555555555555}',
        'ModuleName' => 'Synthetic JSLive Module',
        'Config'     => [
            'Visible'         => 'imported-value',
            'UnknownProperty' => 'must-not-be-added'
        ]
    ],
    JSON_THROW_ON_ERROR
));
$harness->LoadConfigurationFile($validImport);

assertConfigurationTransfer(
    count($state['setConfiguration']) === 1,
    'A valid import must set the instance configuration exactly once.'
);
assertConfigurationTransfer(
    ($state['setConfiguration'][0]['instanceID'] ?? null) === 42,
    'A valid import must target its own instance.'
);
$importedConfiguration = json_decode(
    $state['setConfiguration'][0]['configuration'],
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertConfigurationTransfer(
    ($importedConfiguration['Visible'] ?? null) === 'imported-value',
    'A valid import must update existing properties.'
);
assertConfigurationTransfer(
    ($importedConfiguration['Ignored'] ?? null) === 'synthetic-private-placeholder'
        && ($importedConfiguration['TemplateScriptID'] ?? null) === 0,
    'A valid import must preserve properties that are not part of the payload.'
);
assertConfigurationTransfer(
    !array_key_exists('UnknownProperty', $importedConfiguration),
    'A valid import must not add unknown properties.'
);
assertConfigurationTransfer(
    ($importedConfiguration['LastUploadedConfig'] ?? null) === $validImport,
    'A valid import must retain the uploaded configuration payload.'
);
assertConfigurationTransfer(
    $state['applyChanges'] === [42],
    'A valid import must apply its own instance exactly once.'
);

echo "JSLive configuration transfer contracts verified.\n";
