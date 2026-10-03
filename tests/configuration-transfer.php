<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        protected $InstanceID;

        public function __construct($instanceID)
        {
            $this->InstanceID = $instanceID;
        }

        public function ReadPropertyInteger(string $name): int
        {
            return $GLOBALS['jsliveConfigTransferState']['configuration'][$this->InstanceID][$name] ?? 0;
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

$GLOBALS['jsliveConfigTransferState'] = [
    'configuration'     => [],
    'configurationForm' => [],
    'instances'         => [],
    'scripts'           => [],
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
        return isset($GLOBALS['jsliveConfigTransferState']['scripts'][$scriptID]);
    }
}

function IPS_GetScriptContent(int $scriptID): string
{
    return $GLOBALS['jsliveConfigTransferState']['scripts'][$scriptID]['content'];
}

function IPS_GetObject(int $objectID): array
{
    return ['ObjectName' => $GLOBALS['jsliveConfigTransferState']['scripts'][$objectID]['name']];
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
        return $GLOBALS['jsliveConfigTransferState']['configuration'][$this->InstanceID][$name] ?? 0;
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

function datasetWithoutVariableReferences(string $json): array
{
    return array_map(
        static fn (array $row): array => array_diff_key($row, ['Variable' => true]),
        json_decode($json, true, 512, JSON_THROW_ON_ERROR)
    );
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

// Representative property slices, not complete installations or a new exchange format.
// IDs, names, SVG and script content below are entirely synthetic.
$moduleCases = [
    'SymconJSLiveChart' => [
        'title_text' => 'Synthetic time chart',
        'Datasets'   => json_encode([
            ['Variable' => 41001, 'Axes' => 'temperature', 'Offset' => 1, 'Title' => 'Fixture series']
        ], JSON_THROW_ON_ERROR),
        'Axes' => json_encode([
            ['Ident' => 'temperature', 'Profile' => 'Fixture.Temperature', 'Minimum' => -20, 'Maximum' => 50]
        ], JSON_THROW_ON_ERROR)
    ],
    'SymconJSLiveDoughnutPie' => [
        'type'     => 'doughnut',
        'Datasets' => json_encode([
            ['Order' => 1, 'Variables' => [
                ['Variable' => 41002, 'Label' => 'Fixture A'],
                ['Variable' => 41003, 'Label' => 'Fixture B']
            ]]
        ], JSON_THROW_ON_ERROR)
    ],
    'SymconJSLiveRadarChart' => [
        'customScale_mode' => true,
        'customScale'      => json_encode([
            ['Order' => 0, 'Value' => 0, 'Alias' => 'Fixture north'],
            ['Order' => 1, 'Value' => 180, 'Alias' => 'Fixture south']
        ], JSON_THROW_ON_ERROR),
        'Datasets' => json_encode([
            ['Variable' => 41004, 'Reference' => 41005, 'Mode' => 'counter', 'Offset' => 1]
        ], JSON_THROW_ON_ERROR)
    ],
    'SymconJSLiveGauge' => [
        'Variable'   => 41006,
        'precision'  => 0,
        'template'   => 'CanvasGauges-Radial',
        'Ticks'      => json_encode([['Value' => 0], ['Value' => 100]], JSON_THROW_ON_ERROR),
        'Highlights' => json_encode([
            ['From' => 20, 'To' => 40, 'HighlightColor' => 1122867, 'HighlightColor_Alpha' => 0.25]
        ], JSON_THROW_ON_ERROR)
    ],
    'SymconJSLiveProgressbar' => [
        'Variable'   => 41007,
        'Type'       => 'fill',
        'data_min'   => -20,
        'data_max'   => 80,
        'shape_path' => 'M0 0 L100 0',
        'shape_svg'  => '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="20"/></svg>'
    ]
];

$exportCases = 0;
foreach ($moduleCases as $moduleName => $properties) {
    $moduleRoot = dirname(__DIR__) . '/' . $moduleName;
    $metadata = json_decode(file_get_contents($moduleRoot . '/module.json'), true, 512, JSON_THROW_ON_ERROR);
    $state['instances'][42] = ['ModuleInfo' => ['ModuleID' => $metadata['id'], 'ModuleName' => $metadata['name']]];
    $state['configurationForm'][42] = json_decode(file_get_contents($moduleRoot . '/form.json'), true, 512, JSON_THROW_ON_ERROR);

    foreach ([false, true] as $withTemplate) {
        $state['scripts'] = $withTemplate ? [49001 => ['name' => 'Synthetic template', 'content' => '<p>Fixture template</p>']] : [];
        $state['configuration'][42] = $properties + ['TemplateScriptID' => $withTemplate ? 49001 : 0];
        foreach ([false, true] as $complete) {
            resetConfigurationTransferMutations();
            $before = $state;
            $query = ['scripts' => $withTemplate ? 1 : 0];
            $json = $harness->ExportConfiguration($complete, $query);
            $export = decodeExportedConfiguration($json);
            $context = $moduleName . ($complete ? ' complete' : ' filtered') . ($withTemplate ? ' with template' : ' without template');
            assertConfigurationTransfer($harness->ExportConfiguration($complete, $query) === $json, 'Export is not repeatable: ' . $context);
            assertConfigurationTransfer($state === $before, 'Export mutated source state: ' . $context);
            assertConfigurationTransfer($export['ModuleID'] === $metadata['id'] && $export['ModuleName'] === $metadata['name'], 'Module identity changed: ' . $context);
            assertConfigurationTransfer(
                array_keys($export) === ($withTemplate ? ['ModuleID', 'ModuleName', 'Config', 'Script', 'ScriptName'] : ['ModuleID', 'ModuleName', 'Config']),
                'Legacy export envelope changed: ' . $context
            );
            if ($withTemplate) {
                assertConfigurationTransfer($export['Script'] === '<p>Fixture template</p>' && $export['ScriptName'] === 'Synthetic template', 'Explicit template export changed: ' . $context);
            }

            $expected = $state['configuration'][42];
            $actual = $export['Config'];
            if (!$complete && $moduleName === 'SymconJSLiveChart') {
                unset($expected['title_text']);
                $expected['Datasets'] = json_encode(datasetWithoutVariableReferences($expected['Datasets']), JSON_THROW_ON_ERROR);
            }
            assertConfigurationTransfer($actual === $expected, 'Configuration data changed: ' . $context);
            $exportCases++;
        }
    }
}
echo 'JSLive chart-family exports verified (' . $exportCases . " cases, repeated exports, real static forms).\n";

// Former optional defect probes now run unconditionally, including real child receivers.
$queryCases = [
    [[], false],
    [['scripts' => 0], false],
    [['scripts' => '0'], false],
    [['scripts' => -1], false],
    [['scripts' => 'false'], false],
    [['scripts' => 'yes'], false],
    [['scripts' => [1]], false],
    [['scripts' => null], false],
    [['scripts' => 1], true],
    [['scripts' => '1'], true],
    [['scripts' => '2'], true]
];
$state['scripts'] = [
    49001 => ['name' => 'Synthetic template', 'content' => '<p>Fixture template</p>'],
    49002 => ['name' => 'Synthetic library', 'content' => 'window.fixture = 1;']
];
$state['configuration'][42] = [
    'TemplateScriptID' => 49001,
    'Libraries'        => json_encode([
        ['Ident' => 'fixture-lib', 'Script' => 49002],
        ['Ident' => 'missing-lib', 'Script' => 49003]
    ], JSON_THROW_ON_ERROR)
];
$expectedLibrary = [['Ident' => 'fixture-lib', 'Script' => 'window.fixture = 1;', 'ScriptName' => 'Synthetic library']];
foreach ([false, true] as $complete) {
    foreach ($queryCases as [$query, $withScript]) {
        $before = $state;
        $export = decodeExportedConfiguration($harness->ExportConfiguration($complete, $query));
        assertConfigurationTransfer(isset($export['Script']) === $withScript && isset($export['ScriptName']) === $withScript, 'Template opt-in was not respected.');
        assertConfigurationTransfer(($export['Libraries'] ?? null) === ($withScript ? $expectedLibrary : null), 'Library script opt-in was not respected.');
        assertConfigurationTransfer($export['Config'] === $before['configuration'][42], 'Script selection must not alter configuration references.');
        assertConfigurationTransfer($state === $before, 'Script export changed its source state.');
    }
}
foreach ([0, 49003] as $missingTemplate) {
    $state['configuration'][42]['TemplateScriptID'] = $missingTemplate;
    $export = decodeExportedConfiguration($harness->ExportConfiguration(true, ['scripts' => 1]));
    assertConfigurationTransfer(!isset($export['Script']) && !isset($export['ScriptName']), 'Unavailable template must not be exported.');
    assertConfigurationTransfer($export['Libraries'] === $expectedLibrary, 'A missing template must not suppress an explicitly selected library.');
}

// Only changed lists should be serialized again; JSON scalars and mixed rows must stay safe.
$state['configurationForm'][42] = ['elements' => [
    ['type' => 'List', 'name' => 'Datasets', 'columns' => [['name' => 'Variable', 'ignoreExport' => true]]]
]];
$state['configuration'][42] = [
    'TemplateScriptID' => 0,
    'Datasets'         => '[{"Variable":41001,"Value":1.0,"Items":[{"Variable":41002}]},null,7,{"Variable":41003,"Title":"Fixture"}]',
    'UnchangedList'    => '[ { "Value": 1.0 } ]',
    'ScalarNumber'     => '123',
    'ScalarBoolean'    => 'true',
    'ScalarNull'       => 'null',
    'ScalarString'     => '"fixture"',
    'EmptyList'        => '[]',
    'Malformed'        => '[not-json'
];
$before = $state;
set_error_handler(static function (int $severity, string $message, string $file, int $line): never
{
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    $filtered = decodeExportedConfiguration($harness->ExportConfiguration(false));
    $complete = decodeExportedConfiguration($harness->ExportConfiguration(true));
} finally {
    restore_error_handler();
}
$expected = $before['configuration'][42];
$expected['Datasets'] = '[{"Value":1.0,"Items":[{"Variable":41002}]},null,7,{"Title":"Fixture"}]';
assertConfigurationTransfer($filtered['Config'] === $expected, 'List filtering changed non-ignored values, row order or untouched strings.');
assertConfigurationTransfer($complete['Config'] === $before['configuration'][42], 'Complete export must retain all list references.');
assertConfigurationTransfer($state === $before, 'List filtering changed its source state.');

$receiverCases = 0;
$moduleNames = array_merge(array_keys($moduleCases), [
    'SymconJSLiveAdvTextfield', 'SymconJSLiveColorPicker', 'SymconJSLiveDateTimePicker', 'SymconJSLiveCustom'
]);
foreach ($moduleNames as $moduleName) {
    $moduleRoot = dirname(__DIR__) . '/' . $moduleName;
    require_once $moduleRoot . '/module.php';
    $metadata = json_decode(file_get_contents($moduleRoot . '/module.json'), true, 512, JSON_THROW_ON_ERROR);
    $state['instances'][42] = ['ModuleInfo' => ['ModuleID' => $metadata['id'], 'ModuleName' => $metadata['name']]];
    $state['configurationForm'][42] = json_decode(file_get_contents($moduleRoot . '/form.json'), true, 512, JSON_THROW_ON_ERROR);
    $state['configuration'][42] = ($moduleCases[$moduleName] ?? []) + ['TemplateScriptID' => 49001];
    if ($moduleName === 'SymconJSLiveCustom') {
        $state['configuration'][42]['Libraries'] = '[{"Ident":"fixture-lib","Script":49002}]';
    }
    $module = new $moduleName(42);
    foreach (array_merge($queryCases, [[null, false]]) as [$query, $withScript]) {
        $payload = ['cmd' => 'exportConfiguration', 'instance' => 42];
        if ($query !== null) {
            $payload['queryData'] = $query;
        }
        $message = json_encode([
            'DataID' => '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}', 'InstanceID' => '42',
            'Buffer' => json_encode($payload, JSON_THROW_ON_ERROR)
        ], JSON_THROW_ON_ERROR);
        $before = $state;
        $json = $module->ReceiveData($message);
        $export = decodeExportedConfiguration($json);
        assertConfigurationTransfer($module->ReceiveData($message) === $json, 'Receiver export is not repeatable: ' . $moduleName);
        assertConfigurationTransfer(isset($export['Script']) === $withScript && isset($export['ScriptName']) === $withScript, 'Receiver lost script selection: ' . $moduleName);
        if ($withScript) {
            assertConfigurationTransfer($export['Script'] === '<p>Fixture template</p>', 'Receiver changed template content: ' . $moduleName);
        }
        if ($moduleName === 'SymconJSLiveCustom') {
            assertConfigurationTransfer(($export['Libraries'] ?? null) === ($withScript ? $expectedLibrary : null), 'Custom receiver lost library selection.');
        }
        if ($moduleName === 'SymconJSLiveChart') {
            $datasets = json_decode($export['Config']['Datasets'], true, 512, JSON_THROW_ON_ERROR);
            assertConfigurationTransfer(!isset($export['Config']['title_text']) && !array_key_exists('Variable', $datasets[0]), 'Receiver must use filtered, not complete export.');
        }
        assertConfigurationTransfer($state === $before, 'Receiver changed source state: ' . $moduleName);
        $receiverCases++;
    }
}
echo 'Export selection, list filtering and child receivers verified (' . $receiverCases . " receiver cases).\n";

// A filtered Chart export must not replace an existing target row's variable binding.
$chartMetadata = json_decode(file_get_contents(dirname(__DIR__) . '/SymconJSLiveChart/module.json'), true, 512, JSON_THROW_ON_ERROR);
$state['instances'][42] = ['ModuleInfo' => ['ModuleID' => $chartMetadata['id'], 'ModuleName' => $chartMetadata['name']]];
$state['configurationForm'][42] = json_decode(file_get_contents(dirname(__DIR__) . '/SymconJSLiveChart/form.json'), true, 512, JSON_THROW_ON_ERROR);
$state['configuration'][42] = $moduleCases['SymconJSLiveChart'] + ['TemplateScriptID' => 0];
$filteredSource = $harness->ExportConfiguration();
$state['configuration'][42]['title_text'] = 'Target title';
$state['configuration'][42]['Datasets'] = '[{"Variable":42001,"Title":"Target series"}]';
resetConfigurationTransferMutations();
$harness->LoadConfigurationFile(base64_encode($filteredSource));
$target = json_decode($state['setConfiguration'][0]['configuration'], true, 512, JSON_THROW_ON_ERROR);
$targetRows = json_decode($target['Datasets'], true, 512, JSON_THROW_ON_ERROR);
assertConfigurationTransfer($targetRows[0]['Variable'] === 42001 && $targetRows[0]['Title'] === 'Fixture series', 'Filtered import must preserve the existing target binding and import allowed row fields.');
assertConfigurationTransfer($target['title_text'] === 'Target title' && $state['applyChanges'] === [42], 'Filtered import must preserve the title and apply once.');
echo "Filtered Chart export/import binding preservation verified.\n";
