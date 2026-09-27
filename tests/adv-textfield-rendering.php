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
            return (bool) ($GLOBALS['jsliveRenderingState']['properties'][$name] ?? false);
        }

        public function ReadPropertyInteger(string $name): int
        {
            return (int) ($GLOBALS['jsliveRenderingState']['properties'][$name] ?? 0);
        }

        public function ReadPropertyString(string $name): string
        {
            return (string) ($GLOBALS['jsliveRenderingState']['properties'][$name] ?? '');
        }

        public function GetValue(string $ident): mixed
        {
            return $GLOBALS['jsliveRenderingState']['values'][$ident] ?? null;
        }

        public function SetValue(string $ident, mixed $value): void
        {
            $GLOBALS['jsliveRenderingState']['values'][$ident] = $value;
        }

        public function SetBuffer(string $name, string $value): void
        {
            $GLOBALS['jsliveRenderingState']['buffers'][$name] = $value;
        }

        public function GetBuffer(string $name): string
        {
            return (string) ($GLOBALS['jsliveRenderingState']['buffers'][$name] ?? '');
        }

        public function SendDataToParent(string $json): string
        {
            $GLOBALS['jsliveRenderingState']['parentMessages'][] = $json;

            return array_shift($GLOBALS['jsliveRenderingState']['parentResponses']) ?? '';
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $GLOBALS['jsliveRenderingState']['debugMessages'][] = [
                'message' => $message,
                'data'    => $data
            ];
        }

        public function Translate(string $text): string
        {
            return $text;
        }

        public function UnregisterVariable(string $ident): void
        {
            $GLOBALS['jsliveRenderingState']['unregisteredVariables'][] = $ident;
        }
    }
}

$GLOBALS['jsliveRenderingState'] = [
    'properties'            => [],
    'configuration'         => [],
    'values'                => ['Content' => "Synthetic 'value'\\line"],
    'buffers'               => [],
    'parentMessages'        => [],
    'parentResponses'       => [],
    'debugMessages'         => [],
    'unregisteredVariables' => [],
    'scripts'               => []
];

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode($GLOBALS['jsliveRenderingState']['configuration'], JSON_THROW_ON_ERROR);
    }
}

if (!function_exists('IPS_GetObjectIDByIdent')) {
    function IPS_GetObjectIDByIdent(string $ident, int $instanceID): int|false
    {
        return $ident === 'Content' ? 4201 : false;
    }
}

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return [
            'InstanceStatus' => 102,
            'ConnectionID'   => 7,
            'ModuleInfo'     => [
                'ModuleID'   => '{DBAF2DB0-0FCF-8396-9476-3C087457D012}',
                'ModuleName' => 'SymconJSLiveAdvTextfield'
            ]
        ];
    }
}

if (!function_exists('IPS_ScriptExists')) {
    function IPS_ScriptExists(int $scriptID): bool
    {
        return array_key_exists($scriptID, $GLOBALS['jsliveRenderingState']['scripts']);
    }
}

if (!function_exists('IPS_GetScriptContent')) {
    function IPS_GetScriptContent(int $scriptID): string
    {
        return $GLOBALS['jsliveRenderingState']['scripts'][$scriptID];
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveAdvTextfield/module.php';

final class AdvTextfieldRenderingHarness extends SymconJSLiveAdvTextfield
{
    public function renderWebpage(): string
    {
        return $this->GetWebpage();
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertAdvTextfieldRendering(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function requestAdvTextfieldOutput(AdvTextfieldRenderingHarness $module): array
{
    $response = $module->ReceiveData(json_encode(
        [
            'Buffer' => json_encode(
                ['cmd' => 'getContend', 'queryData' => []],
                JSON_THROW_ON_ERROR
            )
        ],
        JSON_THROW_ON_ERROR
    ));
    $decoded = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
    assertAdvTextfieldRendering(is_array($decoded), 'getContend must return a JSON object.');

    return $decoded;
}

/** @return array{outer: array<string, mixed>, inner: array<string, mixed>} */
function decodeAdvTextfieldParentMessage(string $json): array
{
    $outer = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $inner = json_decode((string) ($outer['Buffer'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    assertAdvTextfieldRendering(is_array($outer), 'The parent message must be a JSON object.');
    assertAdvTextfieldRendering(is_array($inner), 'The parent Buffer must contain a JSON object.');

    return ['outer' => $outer, 'inner' => $inner];
}

function resetAdvTextfieldRenderingInteractions(): void
{
    $GLOBALS['jsliveRenderingState']['parentMessages'] = [];
    $GLOBALS['jsliveRenderingState']['parentResponses'] = [];
    $GLOBALS['jsliveRenderingState']['debugMessages'] = [];
    $GLOBALS['jsliveRenderingState']['unregisteredVariables'] = [];
}

$state = &$GLOBALS['jsliveRenderingState'];
$state['properties'] = [
    'Debug'           => false,
    'Template'        => 'Textfield1',
    'TemplateScriptID' => 0,
    'EnableCache'     => false,
    'EnableViewport'  => true,
    'CreateOutput'    => false,
    'CreateIPSView'   => false,
    'IFrameHeight'    => 0
];
$state['configuration'] = [
    'Template'                       => 'Textfield1',
    'TemplateScriptID'               => 0,
    'EnableCache'                    => false,
    'EnableViewport'                 => true,
    'DataUpdateRate'                 => 50,
    'style_backgroundColor'          => 1122867,
    'style_backgroundColor_Alpha'    => 0.25,
    'style_highlightColor1'          => 4478310,
    'style_highlightColor1_Alpha'    => 0.5,
    'style_fontColor'                => 10531008,
    'style_fontFamily'               => 'Synthetic Font',
    'duplicate_fontFamily'           => 'Synthetic Font',
    'style_borderColor'              => 66051,
    'style_borderWidth'              => 2,
    'style_borderRadius'             => 10,
    'style_fontSize'                 => 12
];

$module = new AdvTextfieldRenderingHarness(42);
$rendered = $module->renderWebpage();

assertAdvTextfieldRendering(
    str_contains($rendered, '<link rel="stylesheet" href="/hook/JSLive/js/css/TextField.css">'),
    'The default template must retain its TextField stylesheet.'
);
assertAdvTextfieldRendering(
    substr_count($rendered, '/hook/JSLive/js/css/fonts/Synthetic Font.css') === 1,
    'Configured fonts must be injected once even when multiple properties use the same family.'
);
assertAdvTextfieldRendering(
    str_contains($rendered, 'style_backgroundColor : "rgba(17, 34, 51, 0.25)"'),
    'Background colors with alpha must be converted to CSS rgba values.'
);
assertAdvTextfieldRendering(
    str_contains($rendered, 'style_fontColor : "rgb(160, 176, 192)"'),
    'Colors without an alpha property must be converted to CSS rgb values.'
);
assertAdvTextfieldRendering(
    str_contains($rendered, 'Variable : 4201') && str_contains($rendered, 'InstanceID : 42'),
    'The rendered configuration must contain the Content variable and instance identifiers.'
);
assertAdvTextfieldRendering(
    str_contains($rendered, "var value = 'Synthetic \\'value\\'\\\\line';"),
    'The current Content value must be inserted as an escaped JavaScript string.'
);
foreach (['{CONFIG}', '{VALUE}', '{FONTS}'] as $modulePlaceholder) {
    assertAdvTextfieldRendering(
        !str_contains($rendered, $modulePlaceholder),
        'The module placeholder was not replaced: ' . $modulePlaceholder
    );
}
foreach (['{GLOBAL}', '{VIEWPORT}', '{INSTANCE}', '{PASSWORD}'] as $parentPlaceholder) {
    assertAdvTextfieldRendering(
        str_contains($rendered, $parentPlaceholder),
        'A parent placeholder was replaced before splitter processing: ' . $parentPlaceholder
    );
}

$state['properties']['TemplateScriptID'] = 9001;
$state['configuration']['TemplateScriptID'] = 9001;
$state['scripts'][9001] = '<section>{FONTS}<script>var value={VALUE};var configuration={CONFIG};</script>'
    . '{GLOBAL}{VIEWPORT}{INSTANCE}{PASSWORD}</section>';
$customTemplate = $module->renderWebpage();
assertAdvTextfieldRendering(
    str_starts_with($customTemplate, '<section>')
        && !str_contains($customTemplate, '/hook/JSLive/js/css/TextField.css'),
    'A configured template script must replace the bundled default template.'
);
foreach (['{CONFIG}', '{VALUE}', '{FONTS}'] as $modulePlaceholder) {
    assertAdvTextfieldRendering(
        !str_contains($customTemplate, $modulePlaceholder),
        'The custom template retained a module placeholder: ' . $modulePlaceholder
    );
}
foreach (['{GLOBAL}', '{VIEWPORT}', '{INSTANCE}', '{PASSWORD}'] as $parentPlaceholder) {
    assertAdvTextfieldRendering(
        str_contains($customTemplate, $parentPlaceholder),
        'The custom template lost a parent placeholder: ' . $parentPlaceholder
    );
}
$state['properties']['TemplateScriptID'] = 0;
$state['configuration']['TemplateScriptID'] = 0;

resetAdvTextfieldRenderingInteractions();
$state['properties']['EnableCache'] = false;
$state['buffers'] = [
    'Output'      => '<html>stale-cache</html>',
    'LastModifed' => 'Mon, 01 Jan 2024 00:00:00 GMT'
];
$uncached = requestAdvTextfieldOutput($module);
assertAdvTextfieldRendering(
    ($uncached['EnableCache'] ?? null) === false
        && ($uncached['InstanceID'] ?? null) === 42
        && str_contains((string) ($uncached['Contend'] ?? ''), '/hook/JSLive/js/css/TextField.css')
        && !str_contains((string) ($uncached['Contend'] ?? ''), 'stale-cache'),
    'A cache-disabled response must render fresh module HTML.'
);
assertAdvTextfieldRendering(
    $state['parentMessages'] === [],
    'A cache-disabled module response must leave splitter placeholders for the webhook layer.'
);

resetAdvTextfieldRenderingInteractions();
$state['properties']['EnableCache'] = true;
$state['buffers'] = [
    'Output'      => '<html>synthetic-cached-output</html>',
    'LastModifed' => 'Tue, 02 Jan 2024 00:00:00 GMT'
];
$cached = requestAdvTextfieldOutput($module);
assertAdvTextfieldRendering(
    ($cached['Contend'] ?? null) === '<html>synthetic-cached-output</html>'
        && ($cached['lastModify'] ?? null) === 'Tue, 02 Jan 2024 00:00:00 GMT'
        && ($cached['EnableCache'] ?? null) === true,
    'A populated cache must be returned without rebuilding the HTML.'
);
assertAdvTextfieldRendering(
    $state['parentMessages'] === [],
    'A populated cache must not send another UpdateHtml message.'
);

resetAdvTextfieldRenderingInteractions();
$state['buffers'] = ['Output' => '', 'LastModifed' => ''];
$state['parentResponses'][] = json_encode(
    ['output' => '<html>splitter-processed-output</html>', 'ipsview' => false],
    JSON_THROW_ON_ERROR
);
$cacheMiss = requestAdvTextfieldOutput($module);
assertAdvTextfieldRendering(
    ($cacheMiss['Contend'] ?? null) === '<html>splitter-processed-output</html>'
        && ($state['buffers']['Output'] ?? null) === '<html>splitter-processed-output</html>',
    'An empty cache must be rebuilt with the splitter-processed HTML.'
);
assertAdvTextfieldRendering(
    is_string($cacheMiss['lastModify'] ?? null)
        && preg_match('/^[A-Z][a-z]{2}, .* GMT$/', $cacheMiss['lastModify']) === 1,
    'A rebuilt cache must receive an HTTP-style GMT modification timestamp.'
);
assertAdvTextfieldRendering(
    count($state['parentMessages']) === 1,
    'An empty cache must send exactly one UpdateHtml message.'
);
$updateMessage = decodeAdvTextfieldParentMessage($state['parentMessages'][0]);
assertAdvTextfieldRendering(
    ($updateMessage['outer']['DataID'] ?? null) === '{751AABD7-E31D-024C-5CC0-82AC15B84095}',
    'UpdateHtml changed its parent DataID.'
);
assertAdvTextfieldRendering(
    ($updateMessage['inner']['Type'] ?? null) === 'UpdateHtml'
        && ($updateMessage['inner']['InstanceID'] ?? null) === 42
        && ($updateMessage['inner']['ViewPort'] ?? null) === true,
    'UpdateHtml changed its routing metadata.'
);
assertAdvTextfieldRendering(
    str_contains((string) ($updateMessage['inner']['Html'] ?? ''), 'style_backgroundColor')
        && !str_contains((string) ($updateMessage['inner']['Html'] ?? ''), '{CONFIG}'),
    'UpdateHtml must send module-rendered HTML to the splitter.'
);

echo "JSLive AdvTextfield rendering contracts verified.\n";
