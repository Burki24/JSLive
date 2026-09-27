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
            'ModuleInfo' => ['ModuleName' => 'SymconJSLiveCalendar']
        ]
    ]
];

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return $GLOBALS['jsliveConfigurationFormState']['instances'][$instanceID];
    }
}

if (!function_exists('IPS_GetInstanceListByModuleID')) {
    function IPS_GetInstanceListByModuleID(string $moduleID): array
    {
        return [];
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveCalendar/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertConfigurationForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

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

/**
 * @param array<string, mixed> $form
 * @return list<array<string, mixed>>
 */
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
function findHeaderDependentRow(array $form): array
{
    foreach (getConfigurationFormItems($form) as $item) {
        if (($item['requireItem'] ?? null) !== 'header_display') {
            continue;
        }

        foreach (flattenConfigurationFormItems($item['items'] ?? []) as $child) {
            if (($child['name'] ?? null) === 'header_backgroundColor') {
                return $item;
            }
        }
    }

    throw new RuntimeException('The synthetic header-dependent row was not found.');
}

/** @return array{module: SymconJSLiveCalendar, form: array<string, mixed>} */
function renderCalendarConfigurationForm(int $viewLevel, bool $headerDisplay): array
{
    $GLOBALS['jsliveConfigurationFormState']['properties'][42] = [
        'Debug'          => false,
        'ViewLevel'      => $viewLevel,
        'header_display' => $headerDisplay,
        'customViews'    => '[]'
    ];

    $module = new SymconJSLiveCalendar(42);
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

$basic = renderCalendarConfigurationForm(0, false);
$disabledExpertOption = findConfigurationFormItem($basic['form'], 'title_fontSize_unitType');
assertConfigurationForm(
    ($disabledExpertOption['visible'] ?? null) === true && ($disabledExpertOption['enabled'] ?? null) === false,
    'A higher-level field marked viewdisable must remain visible and become disabled.'
);
$advancedOnly = findConfigurationFormItem($basic['form'], 'buttons_borderWidth');
assertConfigurationForm(
    ($advancedOnly['visible'] ?? null) === false,
    'A viewlevelexactly field must be hidden outside its configured level.'
);
$hiddenHeaderRow = findHeaderDependentRow($basic['form']);
assertConfigurationForm(
    ($hiddenHeaderRow['visible'] ?? null) === false,
    'A requireItem field must be hidden while its controlling property is false.'
);
assertConfigurationForm(
    is_string($hiddenHeaderRow['name'] ?? null)
        && ($hiddenHeaderRow['name'] ?? '') !== ''
        && ($hiddenHeaderRow['disableExport'] ?? null) === true,
    'An unnamed dynamic field must receive a non-exported technical name.'
);
$headerController = findConfigurationFormItem($basic['form'], 'header_display');
assertConfigurationForm(
    ($headerController['onChange'] ?? null)
        === "SymconJSLiveCalendar_ReloadConfigurationForm(\$id, 'header_display', \$header_display);",
    'A requireItem controller must reload its dependent fields.'
);

$advanced = renderCalendarConfigurationForm(1, true);
$advancedOnly = findConfigurationFormItem($advanced['form'], 'buttons_borderWidth');
assertConfigurationForm(
    ($advancedOnly['visible'] ?? null) === true && ($advancedOnly['caption'] ?? null) === 'Width (Advance)',
    'An advanced field must be visible and labelled at view level 1.'
);
assertConfigurationForm(
    (findConfigurationFormItem($advanced['form'], 'buttons_borderWidth_Expert')['visible'] ?? null) === false,
    'An expert-only field must remain hidden at view level 1.'
);
assertConfigurationForm(
    (findHeaderDependentRow($advanced['form'])['visible'] ?? null) === true,
    'A requireItem field must remain visible while its controlling property is true.'
);

$expert = renderCalendarConfigurationForm(2, true);
$expertOnly = findConfigurationFormItem($expert['form'], 'buttons_borderWidth_Expert');
assertConfigurationForm(
    ($expertOnly['visible'] ?? null) === true && ($expertOnly['caption'] ?? null) === 'Width (CSS) (Expert)',
    'An expert-only field must be visible and labelled at view level 2.'
);
assertConfigurationForm(
    (findConfigurationFormItem($expert['form'], 'buttons_borderWidth')['visible'] ?? null) === false,
    'An exact level-1 field must be hidden at view level 2.'
);

$reload = renderCalendarConfigurationForm(0, false);
$reloadHeaderRow = findHeaderDependentRow($reload['form']);
$GLOBALS['jsliveConfigurationFormState']['updates'] = [];
$reload['module']->ReloadConfigurationForm('header_display', true);
assertConfigurationForm(
    latestConfigurationFormUpdate($reloadHeaderRow['name'], 'visible') === true,
    'ReloadConfigurationForm must reveal requireItem fields when their controller is enabled.'
);

$GLOBALS['jsliveConfigurationFormState']['updates'] = [];
$reload['module']->ReloadConfigurationForm('ViewLevel', 1);
assertConfigurationForm(
    latestConfigurationFormUpdate('buttons_borderWidth', 'visible') === true
        && latestConfigurationFormUpdate('buttons_borderWidth', 'enabled') === true,
    'ReloadConfigurationForm must reveal and enable fields matching the selected view level.'
);
assertConfigurationForm(
    latestConfigurationFormUpdate('buttons_borderWidth_Expert', 'visible') === false,
    'ReloadConfigurationForm must hide exact-level fields that do not match the selected level.'
);
assertConfigurationForm(
    latestConfigurationFormUpdate('title_fontSize_unitType', 'visible') === true
        && latestConfigurationFormUpdate('title_fontSize_unitType', 'enabled') === false,
    'ReloadConfigurationForm must disable higher-level viewdisable fields instead of hiding them.'
);

echo "JSLive configuration form contracts verified.\n";
