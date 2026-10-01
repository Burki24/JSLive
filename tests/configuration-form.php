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

        public function ReadPropertyBoolean(string $name): bool
        {
            return (bool) ($GLOBALS['jsliveConfigurationFormState']['properties'][$this->InstanceID][$name] ?? false);
        }

        public function ReadPropertyInteger(string $name): int
        {
            return (int) ($GLOBALS['jsliveConfigurationFormState']['properties'][$this->InstanceID][$name] ?? 0);
        }

        public function ReadPropertyString(string $name): string
        {
            return (string) ($GLOBALS['jsliveConfigurationFormState']['properties'][$this->InstanceID][$name] ?? '');
        }

        public function SetBuffer(string $name, string $value): void
        {
            $GLOBALS['jsliveConfigurationFormState']['buffers'][$this->InstanceID][$name] = $value;
        }

        public function GetBuffer(string $name): string
        {
            return (string) ($GLOBALS['jsliveConfigurationFormState']['buffers'][$this->InstanceID][$name] ?? '');
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }

        public function Translate(string $text): string
        {
            return $text;
        }

        public function UpdateFormField(string $name, string $property, mixed $value): void
        {
            $GLOBALS['jsliveConfigurationFormState']['updates'][] = [
                'name'     => $name,
                'property' => $property,
                'value'    => $value
            ];
        }
    }
}

$GLOBALS['jsliveConfigurationFormState'] = [
    'properties' => [],
    'buffers'    => [],
    'updates'    => [],
    'instances'  => [
        42 => [
            'ModuleInfo' => ['ModuleName' => 'SymconJSLiveChart']
        ]
    ]
];

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return $GLOBALS['jsliveConfigurationFormState']['instances'][$instanceID];
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveChart/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertConfigurationForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$moduleBaseSource = file_get_contents(dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php');
assertConfigurationForm($moduleBaseSource !== false, 'Unable to read the child-module base source.');
assertConfigurationForm(
    str_contains(
        $moduleBaseSource,
        "require_once dirname(__DIR__, 2) . '/libs/helper/ConfigurationFormHelper.php';"
    ),
    'JSLiveModule must load the vendored ConfigurationFormHelper.'
);
assertConfigurationForm(
    str_contains($moduleBaseSource, 'use \\Burki24\\SymconModuleHelper\\ConfigurationFormHelper {')
        && str_contains($moduleBaseSource, 'LoadConfigurationForm as private LoadStaticConfigurationForm;'),
    'JSLiveModule must alias the helper loader without changing its public LoadConfigurationForm contract.'
);
assertConfigurationForm(
    str_contains($moduleBaseSource, '$formData = $this->LoadStaticConfigurationForm();'),
    'JSLiveModule must load form.json through ConfigurationFormHelper.'
);
assertConfigurationForm(
    !str_contains(
        $moduleBaseSource,
        "realpath(__DIR__ . '/../../' . get_called_class() . '/form.json')"
    ),
    'JSLiveModule must not keep its manual configuration-form path resolver.'
);

/**
 * @param list<array<string, mixed>> $items
 * @return list<array<string, mixed>>
 */
function flattenConfigurationFormItems(array $items): array
{
    $flattened = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $flattened[] = $item;
        foreach (['items', 'options', 'columns'] as $childKey) {
            if (isset($item[$childKey]) && is_array($item[$childKey])) {
                $flattened = array_merge($flattened, flattenConfigurationFormItems($item[$childKey]));
            }
        }

        if (isset($item['edit']) && is_array($item['edit'])) {
            foreach (['options', 'columns'] as $childKey) {
                if (isset($item['edit'][$childKey]) && is_array($item['edit'][$childKey])) {
                    $flattened = array_merge(
                        $flattened,
                        flattenConfigurationFormItems($item['edit'][$childKey])
                    );
                }
            }
        }
    }

    return $flattened;
}

/** @param array<string, mixed> $form */
function getConfigurationFormItems(array $form): array
{
    return array_merge(
        flattenConfigurationFormItems($form['elements'] ?? []),
        flattenConfigurationFormItems($form['actions'] ?? [])
    );
}

/** @param array<string, mixed> $form */
function findConfigurationFormItem(array $form, string $name): array
{
    foreach (getConfigurationFormItems($form) as $item) {
        if (($item['name'] ?? null) === $name) {
            return $item;
        }
    }

    throw new RuntimeException('Configuration form item not found: ' . $name);
}

/** @param array<string, mixed> $form */
function findTitleDependentRow(array $form): array
{
    foreach (getConfigurationFormItems($form) as $item) {
        if (($item['requireItem'] ?? null) !== 'title_display') {
            continue;
        }

        foreach (flattenConfigurationFormItems($item['items'] ?? []) as $child) {
            if (($child['name'] ?? null) === 'title_fontSize') {
                return $item;
            }
        }
    }

    throw new RuntimeException('The title-dependent row was not found.');
}

/** @return array{module: SymconJSLiveChart, form: array<string, mixed>} */
function renderChartConfigurationForm(int $viewLevel, bool $titleDisplay): array
{
    $GLOBALS['jsliveConfigurationFormState']['properties'][42] = [
        'Debug'               => false,
        'ViewLevel'           => $viewLevel,
        'title_display'       => $titleDisplay,
        'Axes'                => '[]',
        'Datasets'            => '[]',
        'xaxes_override_list' => '[]'
    ];

    $module = new SymconJSLiveChart(42);
    $form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
    assertConfigurationForm(
        is_array($form) && is_array($form['elements'] ?? null) && is_array($form['actions'] ?? null),
        'GetConfigurationForm must return a valid form root.'
    );

    return ['module' => $module, 'form' => $form];
}

function latestConfigurationFormUpdate(string $name, string $property): mixed
{
    $updates = array_reverse($GLOBALS['jsliveConfigurationFormState']['updates']);
    foreach ($updates as $update) {
        if ($update['name'] === $name && $update['property'] === $property) {
            return $update['value'];
        }
    }

    throw new RuntimeException('Configuration form update not found: ' . $name . '.' . $property);
}

$basic = renderChartConfigurationForm(0, false);
assertConfigurationForm(
    (findConfigurationFormItem($basic['form'], 'xaxes_overrideDynamic')['visible'] ?? null) === false,
    'An expert field must be hidden at the basic view level.'
);
$hiddenTitleRow = findTitleDependentRow($basic['form']);
assertConfigurationForm(
    ($hiddenTitleRow['visible'] ?? null) === false,
    'A requireItem field must be hidden while its controlling property is false.'
);
assertConfigurationForm(
    is_string($hiddenTitleRow['name'] ?? null)
        && ($hiddenTitleRow['name'] ?? '') !== ''
        && ($hiddenTitleRow['disableExport'] ?? null) === true,
    'An unnamed dynamic field must receive a non-exported technical name.'
);
$titleController = findConfigurationFormItem($basic['form'], 'title_display');
assertConfigurationForm(
    ($titleController['onChange'] ?? null)
        === "SymconJSLiveChart_ReloadConfigurationForm(\$id, 'title_display', (string) \$title_display);",
    'A requireItem controller must reload its dependent fields.'
);

$advanced = renderChartConfigurationForm(1, true);
assertConfigurationForm(
    (findTitleDependentRow($advanced['form'])['visible'] ?? null) === true,
    'A requireItem field must remain visible while its controlling property is true.'
);
assertConfigurationForm(
    (findConfigurationFormItem($advanced['form'], 'xaxes_overrideDynamic')['visible'] ?? null) === false,
    'An expert field must remain hidden at view level 1.'
);

$expert = renderChartConfigurationForm(2, true);
$expertOnly = findConfigurationFormItem($expert['form'], 'xaxes_overrideDynamic');
assertConfigurationForm(
    ($expertOnly['visible'] ?? null) === true
        && ($expertOnly['caption'] ?? null) === 'override dynamic scaling (Expert)',
    'An expert field must be visible and labelled at view level 2.'
);

$reload = renderChartConfigurationForm(0, false);
$reloadTitleRow = findTitleDependentRow($reload['form']);
$GLOBALS['jsliveConfigurationFormState']['updates'] = [];
$reload['module']->ReloadConfigurationForm('title_display', 'true');
assertConfigurationForm(
    latestConfigurationFormUpdate($reloadTitleRow['name'], 'visible') === true,
    'ReloadConfigurationForm must reveal requireItem fields when their controller is enabled.'
);

$GLOBALS['jsliveConfigurationFormState']['updates'] = [];
$reload['module']->ReloadConfigurationForm('title_display', 'false');
assertConfigurationForm(
    latestConfigurationFormUpdate($reloadTitleRow['name'], 'visible') === false,
    'ReloadConfigurationForm must hide requireItem fields when their controller is disabled.'
);

$GLOBALS['jsliveConfigurationFormState']['updates'] = [];
$reload['module']->ReloadConfigurationForm('ViewLevel', '2');
assertConfigurationForm(
    latestConfigurationFormUpdate('xaxes_overrideDynamic', 'visible') === true
        && latestConfigurationFormUpdate('xaxes_overrideDynamic', 'enabled') === true,
    'ReloadConfigurationForm must reveal and enable fields matching the selected view level.'
);

echo "JSLive configuration form contracts verified.\n";
