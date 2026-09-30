<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        protected int $InstanceID;

        public function __construct(int $instanceID = 42)
        {
            $this->InstanceID = $instanceID;
        }

        public function SetBuffer(string $name, string $value): void
        {
            $GLOBALS['jslivePublicFormCallbackState']['buffers'][$name] = $value;
        }

        public function GetBuffer(string $name): string
        {
            return (string) ($GLOBALS['jslivePublicFormCallbackState']['buffers'][$name] ?? '');
        }

        public function UpdateFormField(string $name, string $property, mixed $value): void
        {
            $GLOBALS['jslivePublicFormCallbackState']['updates'][] = [
                'name'     => $name,
                'property' => $property,
                'value'    => $value
            ];
        }
    }
}

$GLOBALS['jslivePublicFormCallbackState'] = [
    'buffers' => [],
    'updates' => []
];

if (!function_exists('IPS_VariableProfileExists')) {
    function IPS_VariableProfileExists(string $profile): bool
    {
        return false;
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveChart/module.php';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertPublicFormCallback(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function findNamedFormItem(array $items, string $name): array
{
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (($item['name'] ?? null) === $name) {
            return $item;
        }
        foreach (['items', 'options', 'columns'] as $childKey) {
            if (!isset($item[$childKey]) || !is_array($item[$childKey])) {
                continue;
            }
            $match = findNamedFormItem($item[$childKey], $name);
            if ($match !== []) {
                return $match;
            }
        }
    }

    return [];
}

$supportedSignatures = [
    [JSLiveModule::class, 'ReloadConfigurationForm', ['string', 'string']],
    [SymconJSLiveChart::class, 'ReloadFormAxes', ['string', 'int']],
    [SymconJSLiveChart::class, 'ReloadFormDatasets', ['string', 'int']]
];

foreach ($supportedSignatures as [$class, $method, $expectedTypes]) {
    $reflection = new ReflectionMethod($class, $method);
    $actualTypes = array_map(
        static fn (ReflectionParameter $parameter): ?string => $parameter->getType()?->getName(),
        $reflection->getParameters()
    );
    assertPublicFormCallback(
        $actualTypes === $expectedTypes,
        $class . '::' . $method . ' must expose only supported scalar parameter types.'
    );
    assertPublicFormCallback(
        $reflection->getReturnType()?->getName() === 'void',
        $class . '::' . $method . ' must declare its void return type.'
    );
}

$chartForm = json_decode(
    file_get_contents(dirname(__DIR__) . '/SymconJSLiveChart/form.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$axesField = findNamedFormItem($chartForm['elements'] ?? [], 'Axes');
$datasetsField = findNamedFormItem($chartForm['elements'] ?? [], 'Datasets');

foreach (['onAdd', 'onEdit', 'onDelete'] as $callback) {
    assertPublicFormCallback(
        str_contains((string) ($axesField[$callback] ?? ''), 'json_encode($Axes)'),
        'The Axes ' . $callback . ' callback must transport its row as JSON.'
    );
}
foreach (['onAdd', 'onEdit'] as $callback) {
    assertPublicFormCallback(
        str_contains((string) ($datasetsField[$callback] ?? ''), 'json_encode($Datasets)'),
        'The Datasets ' . $callback . ' callback must transport its row as JSON.'
    );
}

$module = new SymconJSLiveChart(42);
$module->SetBuffer('ConfigurationBuffer', json_encode(
    ['elements' => [['name' => 'Axes']]],
    JSON_THROW_ON_ERROR
));
$module->SetBuffer('AxesBuffer', '[]');
$axis = [
    'Ident'            => 'synthetic-axis',
    'Profile'          => '',
    'DynScale'         => true,
    'OverrideMinMax'   => false,
    'Minimum'          => 0,
    'Maximum'          => 100,
    'OverrideStepSize' => false
];

$module->ReloadFormAxes(json_encode($axis, JSON_THROW_ON_ERROR), 0);
$storedAxes = json_decode($module->GetBuffer('AxesBuffer'), true, 512, JSON_THROW_ON_ERROR);
assertPublicFormCallback(
    ($storedAxes[0]['Ident'] ?? null) === 'synthetic-axis'
        && ($storedAxes[0]['Min'] ?? null) === 'Dyn.'
        && ($storedAxes[0]['Max'] ?? null) === 'Dyn.',
    'ReloadFormAxes must decode and process a JSON-transported form row.'
);

$beforeInvalidRow = $module->GetBuffer('AxesBuffer');
$module->ReloadFormAxes('{invalid-json', 0);
assertPublicFormCallback(
    $module->GetBuffer('AxesBuffer') === $beforeInvalidRow,
    'ReloadFormAxes must ignore an invalid JSON transport value.'
);

$module->ReloadFormDatasets('[]', 3);

echo "JSLive public form callback contracts verified.\n";
