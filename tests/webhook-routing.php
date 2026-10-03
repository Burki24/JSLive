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
    }
}

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode(
            ['InstanceID' => $instanceID, 'Source' => 'webhook-test'],
            JSON_THROW_ON_ERROR
        );
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/module.php';

final class WebhookRoutingHarness extends SymconJSLive
{
    /** @var list<string> */
    public array $childMessages = [];

    /** @var list<array{message: string, data: string}> */
    public array $debugMessages = [];

    /** @var list<int> */
    public array $responseStatusCodes = [];
    /** @var array<string, bool|int|string> */
    private array $properties = [
        'Debug'             => false,
        'Password'          => 'synthetic-secret',
        'enableCache'       => false,
        'enableCompression' => false,
        'EnableViewport'    => true,
        'viewport_content'  => 'width=device-width',
        'Address'           => 'http://127.0.0.1:3777'
    ];

    /** @var list<string> */
    private array $childResponses = [];

    public function __construct()
    {
        parent::__construct(42);
    }

    /** @param list<string> $responses */
    public function setChildResponses(array $responses): void
    {
        $this->childResponses = $responses;
    }

    public function resetCapturedData(): void
    {
        $this->childResponses = [];
        $this->childMessages = [];
        $this->debugMessages = [];
        $this->responseStatusCodes = [];
    }

    public function setDebugEnabled(bool $enabled): void
    {
        $this->properties['Debug'] = $enabled;
    }

    public function setPassword(string $password): void
    {
        $this->properties['Password'] = $password;
    }

    public function setCacheEnabled(bool $enabled): void
    {
        $this->properties['enableCache'] = $enabled;
    }

    public function setCompressionEnabled(bool $enabled): void
    {
        $this->properties['enableCompression'] = $enabled;
    }

    public function resolveImageMimeTypeForTest(mixed $mimeType): string
    {
        return $this->NormalizeImageMimeType($mimeType);
    }

    public function resolveStaticMimeTypeForTest(string $extension): string
    {
        return parent::GetMimeType($extension);
    }

    public function buildDownloadContentDispositionForTest(string $filename): string
    {
        return $this->BuildDownloadContentDisposition($filename);
    }

    public function ReadPropertyBoolean(string $name): bool
    {
        return (bool) ($this->properties[$name] ?? false);
    }

    public function ReadPropertyString(string $name): string
    {
        return (string) ($this->properties[$name] ?? '');
    }

    public function SendDataToChildren(string $json): array
    {
        $this->childMessages[] = $json;

        return $this->childResponses;
    }

    public function SendDebug(string $message, string $data, int $format): void
    {
        $this->debugMessages[] = ['message' => $message, 'data' => $data];
    }

    /**
     * @param array<string, string|null> $serverOverrides
     *
     * @return array{output: string, result: mixed, statusCode: int|false}
     */
    public function route(
        string $scriptName,
        string $queryString,
        array $post = [],
        array $serverOverrides = []
    ): array {
        $previousServer = $_SERVER;
        $previousPost = $_POST;
        header_remove();
        $_SERVER = array_merge([
            'SCRIPT_NAME'          => $scriptName,
            'QUERY_STRING'         => $queryString,
            'HTTP_ACCEPT_ENCODING' => ''
        ], $serverOverrides);
        foreach ($serverOverrides as $name => $value) {
            if ($value === null) {
                unset($_SERVER[$name]);
            }
        }
        $_POST = $post;

        ob_start();
        try {
            $result = $this->ProcessHookData();
            $output = ob_get_clean();
            $statusCode = http_response_code();
        } catch (Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        } finally {
            $_SERVER = $previousServer;
            $_POST = $previousPost;
            header_remove();
        }

        return ['output' => (string) $output, 'result' => $result, 'statusCode' => $statusCode];
    }

    protected function GetMimeType($extension)
    {
        return 'application/octet-stream';
    }

    protected function SendPlainTextResponse(int $statusCode, string $message): void
    {
        $this->responseStatusCodes[] = $statusCode;
        echo $message;
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertWebhookRouting(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function decodeWebhookMessage(string $json): array
{
    $outer = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    assertWebhookRouting(is_array($outer), 'The child message must be a JSON object.');
    $inner = json_decode((string) ($outer['Buffer'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    assertWebhookRouting(is_array($inner), 'The child Buffer must contain a JSON object.');

    return ['outer' => $outer, 'inner' => $inner];
}

$harness = new WebhookRoutingHarness();

$updateHtmlResponse = json_decode(
    (string) $harness->ForwardData(json_encode([
        'Buffer' => json_encode([
            'Type'       => 'UpdateHtml',
            'InstanceID' => 49951,
            'Html'       => 'Instance {INSTANCE}',
            'ViewPort'   => false
        ], JSON_THROW_ON_ERROR)
    ], JSON_THROW_ON_ERROR)),
    true,
    512,
    JSON_THROW_ON_ERROR
);
assertWebhookRouting(
    ($updateHtmlResponse['output'] ?? null) === 'Instance 49951',
    'Numeric instance IDs must be converted safely when replacing HTML placeholders.'
);

foreach (['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml'] as $imageMimeType) {
    assertWebhookRouting(
        $harness->resolveImageMimeTypeForTest($imageMimeType) === $imageMimeType,
        'Configured image MIME types must remain available.'
    );
}
assertWebhookRouting(
    $harness->resolveStaticMimeTypeForTest('woff2') === 'font/woff2',
    'Vendored WOFF2 fonts must be served with the font/woff2 MIME type.'
);
foreach (['text/html', "image/png\r\nX-Test: injected", '', ['image/png']] as $invalidMimeType) {
    assertWebhookRouting(
        $harness->resolveImageMimeTypeForTest($invalidMimeType) === 'application/octet-stream',
        'Unknown or malformed image MIME types must use the binary fallback.'
    );
}

$regularDownloadName = 'JSLive Chart_Wohnzimmer_ID42_2026-09-28_12-34-56.json';
assertWebhookRouting(
    $harness->buildDownloadContentDispositionForTest($regularDownloadName)
        === 'attachment; filename="JSLive Chart_Wohnzimmer_ID42_2026-09-28_12-34-56.json"; '
            . "filename*=UTF-8''JSLive%20Chart_Wohnzimmer_ID42_2026-09-28_12-34-56.json",
    'Regular export filenames must preserve the established readable name.'
);

$unsafeDownloadHeader = $harness->buildDownloadContentDispositionForTest(
    "Chart\"\r\nX-Test: injected_Übersicht/Bad\\Name_ID42_2026-09-28_12-34-56.json"
);
assertWebhookRouting(
    !str_contains($unsafeDownloadHeader, "\r")
        && !str_contains($unsafeDownloadHeader, "\n")
        && !str_contains($unsafeDownloadHeader, 'X-Test:')
        && str_contains($unsafeDownloadHeader, 'filename="Chart_X-Test_ injected__bersicht_Bad_Name_')
        && str_contains($unsafeDownloadHeader, "filename*=UTF-8''Chart_X-Test_%20injected_%C3%9Cbersicht_Bad_Name_"),
    'Export filenames must not inject headers or unsafe filesystem characters and must preserve UTF-8 names.'
);

$imageBytes = 'synthetic-image-bytes';
$harness->setChildResponses([
    json_encode([
        'Contend' => base64_encode($imageBytes),
        'Type'    => "image/png\r\nX-Test: injected"
    ], JSON_THROW_ON_ERROR)
]);
$imageResponse = $harness->route(
    '/hook/JSLive/getFillImg',
    'instance=42&pw=synthetic-secret'
);
assertWebhookRouting(
    $imageResponse['output'] === $imageBytes,
    'Image MIME normalization must not alter the established response body.'
);
$harness->resetCapturedData();

$assetResponse = $harness->route('/hook/JSLive/js/util.js', '');
assertWebhookRouting(
    $assetResponse['output'] === file_get_contents(dirname(__DIR__) . '/SymconJSLive/js/util.js'),
    'A JavaScript asset inside the public directory must remain available.'
);

$fontAsset = 'TUZyzwprpvBS1izr_vOECuSf.woff2';
foreach (['css/DatePicker1.css', 'css/fonts/Roboto.css'] as $stylesheetAsset) {
    $stylesheetResponse = $harness->route('/hook/JSLive/js/' . $stylesheetAsset, '');
    assertWebhookRouting(
        $stylesheetResponse['statusCode'] === 200
            && $stylesheetResponse['output'] === file_get_contents(dirname(__DIR__) . '/SymconJSLive/js/' . $stylesheetAsset),
        'Active date picker and font stylesheets must remain available after the legacy asset cleanup.'
    );
}
foreach ([
    'chartjs/chart.js',
    'jquery.min.js',
    'jquery/4.0.0/jquery.min.js',
    'jquery/4.0.0/jquery.min.map',
    'canvas-gauges/gauge.min.js',
    'chartjs/chart.min.js',
    'chartjs/4.5.1/chart.umd.min.js',
    'chartjs/4.5.1/chart.umd.min.js.map',
    'moment/2.27.0/Moment.js',
    'moment/2.31.0/moment.min.js',
    'moment/2.31.0/moment.min.js.map',
    'chartjs/plugins/chartjs-adapter-moment.js',
    'chartjs/plugins/moment/1.0.1/chartjs-adapter-moment.min.js',
    'chartjs/plugins/chartjs-plugin-datalabels.min.js',
    'chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js',
    'chartjs/plugins/streaming/3.6.0/chartjs-plugin-streaming.min.js',
    'chartjs/plugins/chartjs-plugin-streaming.min.js'
] as $chartAsset) {
    $chartResponse = $harness->route('/hook/JSLive/js/' . $chartAsset, '');
    assertWebhookRouting(
        $chartResponse['statusCode'] === 200
            && $chartResponse['output'] === file_get_contents(dirname(__DIR__) . '/SymconJSLive/js/' . $chartAsset),
        'Active frontend bundles and plugins must remain available without substitution: ' . $chartAsset
    );
}
foreach ([
    'mc-calendar/mc-calendar.min.js',
    'css/DateTimePicker1.css',
    'css/font-face.css',
    'chartjs/chart.esm.js',
    'chartjs/chart.mjs',
    'chartjs/helpers.esm.js',
    'chartjs/helpers.mjs',
    'chartjs/3.6.0/chart.min.js',
    'chartjs/3.6.0/plugins/chartjs-adapter-moment.js',
    'chartjs/3.6.0/plugins/chartjs-plugin-datalabels.min.js',
    'chartjs/3.6.0/plugins/chartjs-plugin-streaming.min.js',
    'chartjs/plugins/chartjs-plugin-datalabels.js'
] as $removedAsset) {
    $harness->resetCapturedData();
    $removedAssetResponse = $harness->route('/hook/JSLive/js/' . $removedAsset, '');
    assertWebhookRouting(
        $harness->responseStatusCodes === [404] && $removedAssetResponse['output'] === '',
        'Removed legacy asset must return HTTP 404 without a replacement body: ' . $removedAsset
    );
}
$harness->resetCapturedData();

$fontResponse = $harness->route('/hook/JSLive/js/fonts/' . $fontAsset, '');
assertWebhookRouting(
    $fontResponse['output'] === file_get_contents(dirname(__DIR__) . '/SymconJSLive/js/fonts/' . $fontAsset),
    'A vendored WOFF2 font inside the public directory must remain available.'
);

$escapedAssetResponse = $harness->route('/hook/JSLive/js/../module.php', '');
assertWebhookRouting(
    $escapedAssetResponse['output'] === '',
    'The asset route must not expose files outside the public JavaScript directory.'
);
assertWebhookRouting(
    $harness->responseStatusCodes === [404],
    'An asset path outside the public JavaScript directory must return HTTP 404.'
);
$harness->resetCapturedData();

$initResponse = $harness->route(
    '/hook/JSLive/js/init.js',
    'INTID=42&BOXID=box%22id&PW=secret%22%3Balert%281%29%3B%2F%2F'
        . '&LINK=https%3A%2F%2Fexample.test%2Fhook%3Fa%3D1%26b%3D2'
);
assertWebhookRouting(
    !str_contains($initResponse['output'], 'secret";alert(1);//')
        && str_contains($initResponse['output'], 'secret\\u0022;alert(1);\\/\\/')
        && str_contains($initResponse['output'], 'https:\\/\\/example.test\\/hook?a=1\\u0026b=2'),
    'init.js query values must be decoded and escaped as JavaScript strings.'
);

$invalidInstanceResponse = $harness->route(
    '/hook/JSLive/js/init.js',
    'intid=alert%281%29&boxid=box&pw=secret&link=https%3A%2F%2Fexample.test'
);
assertWebhookRouting(
    !str_contains($invalidInstanceResponse['output'], 'load_Inctance(alert(1)'),
    'init.js must not insert executable query data as its numeric instance ID.'
);

$invalidUtf8Response = $harness->route(
    '/hook/JSLive/js/init.js',
    'intid=42&boxid=%FF&pw=&link='
);
assertWebhookRouting(
    $invalidUtf8Response['output'] !== '' && str_contains($invalidUtf8Response['output'], '\\ufffd'),
    'init.js must replace invalid UTF-8 query bytes instead of aborting the response.'
);

$harness->setCacheEnabled(true);
$assetBody = file_get_contents(dirname(__DIR__) . '/SymconJSLive/js/util.js');
$cachedAssetResponse = $harness->route(
    '/hook/JSLive/js/util.js',
    '',
    [],
    ['HTTP_IF_NONE_MATCH' => '"' . md5((string) $assetBody) . '"']
);
assertWebhookRouting(
    $cachedAssetResponse['output'] === '' && $cachedAssetResponse['statusCode'] === 304,
    'An unchanged JavaScript asset must return HTTP 304 without a response body.'
);

$harness->resetCapturedData();
$harness->setChildResponses([
    json_encode(
        [
            'InstanceID'     => '42',
            'Contend'        => '<div>cached fixture</div>',
            'lastModify'     => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'EnableCache'    => true,
            'EnableViewport' => true
        ],
        JSON_THROW_ON_ERROR
    )
]);
$cachedContentBody = '<div>cached fixture</div>';
$cachedContentResponse = $harness->route(
    '/hook/JSLive/',
    'instance=42&pw=synthetic-secret',
    [],
    ['HTTP_IF_NONE_MATCH' => md5($cachedContentBody)]
);
assertWebhookRouting(
    $cachedContentResponse['output'] === '' && $cachedContentResponse['statusCode'] === 304,
    'An unchanged cached module response must return HTTP 304 without a response body.'
);
$cachedContentDateResponse = $harness->route(
    '/hook/JSLive/',
    'instance=42&pw=synthetic-secret',
    [],
    ['HTTP_IF_MODIFIED_SINCE' => 'Mon, 01 Jan 2024 00:00:00 GMT']
);
assertWebhookRouting(
    $cachedContentDateResponse['output'] === '' && $cachedContentDateResponse['statusCode'] === 304,
    'An unchanged cached module response must honor If-Modified-Since without a response body.'
);
$harness->setCacheEnabled(false);
$harness->resetCapturedData();

$harness->setChildResponses([
    json_encode(
        [
            'InstanceID'     => '42',
            'Contend'        => '<div>Instance {INSTANCE}</div>',
            'lastModify'     => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'EnableCache'    => false,
            'EnableViewport' => true
        ],
        JSON_THROW_ON_ERROR
    )
]);
$uncachedContentResponse = $harness->route(
    '/hook/JSLive/',
    'instance=42&pw=synthetic-secret'
);
assertWebhookRouting(
    $uncachedContentResponse['output'] === '<div>Instance 42</div>',
    'An uncached module response must accept the numeric instance ID from the query string.'
);
$harness->resetCapturedData();

$deniedResponse = $harness->route('/hook/JSLive/getData', 'instance=42&pw=wrong-secret');
assertWebhookRouting($deniedResponse['output'] === '', 'A rejected password must not produce response data.');
assertWebhookRouting($harness->childMessages === [], 'A rejected password must not reach child modules.');
assertWebhookRouting(
    $harness->responseStatusCodes === [200],
    'A rejected password must preserve the non-disclosing HTTP 200 response.'
);
$deniedDebug = json_encode($harness->debugMessages, JSON_THROW_ON_ERROR);
assertWebhookRouting(!str_contains($deniedDebug, 'wrong-secret'), 'Rejected passwords must not be logged.');
assertWebhookRouting(!str_contains($deniedDebug, 'synthetic-secret'), 'Configured passwords must not be logged.');
assertWebhookRouting(str_contains($deniedDebug, '***'), 'Rejected password diagnostics must be masked.');

$harness->resetCapturedData();
$harness->setChildResponses(['OK']);
$writeResponse = $harness->route(
    '/hook/JSLive/setData',
    'instance=42&pw=synthetic-secret&obj=17&val=synthetic-write',
    [],
    ['REQUEST_METHOD' => 'GET']
);
assertWebhookRouting($writeResponse['output'] === 'OK', 'An authenticated GET write changed its response body.');
assertWebhookRouting(count($harness->childMessages) === 1, 'An authenticated GET write must reach one child module.');
$writeMessage = decodeWebhookMessage($harness->childMessages[0]);
assertWebhookRouting(
    ($writeMessage['inner']['cmd'] ?? null) === 'setData'
        && ($writeMessage['inner']['queryData']['obj'] ?? null) === '17'
        && ($writeMessage['inner']['queryData']['val'] ?? null) === 'synthetic-write',
    'The established setData GET contract must forward its target and value unchanged.'
);

$harness->resetCapturedData();
$harness->setPassword('');
$harness->setChildResponses(['{"value":44}']);
$passwordlessResponse = $harness->route('/hook/JSLive/getData', 'instance=42');
assertWebhookRouting(
    $passwordlessResponse['output'] === '{"value":44}' && count($harness->childMessages) === 1,
    'An empty configured password must preserve the established authentication bypass.'
);
$harness->setPassword('synthetic-secret');

$harness->resetCapturedData();
$harness->setChildResponses(['{"value":42}']);
$dataResponse = $harness->route(
    '/hook/JSLive/getData',
    'instance=42&pw=synthetic-secret&var=17'
);
assertWebhookRouting($dataResponse['output'] === '{"value":42}', 'getData changed its response body.');
assertWebhookRouting(count($harness->childMessages) === 1, 'getData must forward exactly one child message.');
$dataMessage = decodeWebhookMessage($harness->childMessages[0]);
assertWebhookRouting(
    ($dataMessage['outer']['DataID'] ?? null) === '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}',
    'getData changed the child DataID.'
);
assertWebhookRouting(($dataMessage['inner']['cmd'] ?? null) === 'getData', 'getData changed its command name.');
assertWebhookRouting(($dataMessage['inner']['instance'] ?? null) === '42', 'getData changed its instance value.');
assertWebhookRouting(
    ($dataMessage['inner']['queryData']['var'] ?? null) === '17',
    'getData no longer forwards module-specific query values.'
);

$harness->resetCapturedData();
$harness->setCompressionEnabled(true);
$harness->setChildResponses(['{"value":42}']);
set_error_handler(static function (int $severity, string $message, string $file, int $line): never
{
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    $headerlessDataResponse = $harness->route(
        '/hook/JSLive/getData',
        'instance=42&pw=synthetic-secret&var=17',
        [],
        ['HTTP_ACCEPT_ENCODING' => null]
    );
} finally {
    restore_error_handler();
}
assertWebhookRouting(
    $headerlessDataResponse['output'] === '{"value":42}',
    'A request without Accept-Encoding must retain the uncompressed response body.'
);
$harness->setCompressionEnabled(false);

$harness->resetCapturedData();
$harness->setPassword('synthetic secret+=&');
$harness->setChildResponses(['{"value":43}']);
$encodedResponse = $harness->route(
    '/hook/JSLive/getData',
    'INSTANCE=42&PW=synthetic%20secret%2B%3D%26&title=Chart%20A%2BB&token=a%3Db'
);
assertWebhookRouting(
    $encodedResponse['output'] === '{"value":43}',
    'Equivalent URL encoding must not reject a valid password.'
);
$encodedMessage = decodeWebhookMessage($harness->childMessages[0]);
assertWebhookRouting(
    ($encodedMessage['inner']['queryData']['title'] ?? null) === 'Chart A+B'
        && ($encodedMessage['inner']['queryData']['token'] ?? null) === 'a=b',
    'Webhook query values must be URL-decoded without losing embedded equals signs.'
);

$harness->resetCapturedData();
$harness->setPassword('0e123456789');
$numericPasswordResponse = $harness->route('/hook/JSLive/getData', 'instance=42&pw=0e987654321');
assertWebhookRouting(
    $numericPasswordResponse['output'] === '' && $harness->childMessages === [],
    'Different numeric-looking password strings must not authenticate as equal.'
);
$harness->setPassword('synthetic-secret');

$harness->resetCapturedData();
$missingInstanceResponse = $harness->route('/hook/JSLive/getData', 'pw=synthetic-secret');
assertWebhookRouting($missingInstanceResponse['output'] === '', 'A missing instance must not produce response data.');
assertWebhookRouting($harness->childMessages === [], 'A missing instance must not reach child modules.');
assertWebhookRouting(
    $harness->responseStatusCodes === [400],
    'A missing instance query parameter must return HTTP 400.'
);

$harness->resetCapturedData();
$missingChildResponse = $harness->route(
    '/hook/JSLive/getData',
    'instance=42&pw=synthetic-secret'
);
assertWebhookRouting(
    $missingChildResponse['output'] === 'NO INSTANCE FOUND!',
    'A missing child instance must return the existing diagnostic body.'
);
assertWebhookRouting(
    $harness->responseStatusCodes === [404],
    'A missing child instance must return HTTP 404.'
);

$harness->resetCapturedData();
$globalConfigResponse = $harness->route(
    '/hook/JSLive/getGlobalConfig',
    'pw=synthetic-secret'
);
assertWebhookRouting(
    $globalConfigResponse['output'] === '{"InstanceID":42,"Source":"webhook-test"}',
    'getGlobalConfig changed its direct response contract.'
);
assertWebhookRouting($harness->childMessages === [], 'getGlobalConfig must not be sent to child modules.');

$harness->resetCapturedData();
$harness->setChildResponses([
    json_encode(
        [
            'InstanceID'     => '99',
            'Contend'        => '<div>other instance</div>',
            'lastModify'     => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'EnableCache'    => true,
            'EnableViewport' => true
        ],
        JSON_THROW_ON_ERROR
    )
]);
$unmatchedContentResponse = $harness->route('/hook/JSLive/', 'instance=42&pw=synthetic-secret');
assertWebhookRouting(
    $unmatchedContentResponse['output'] === 'Instance Not in List!'
        && $harness->responseStatusCodes === [404],
    'An unmatched content instance must return its existing message with HTTP 404.'
);

$harness->resetCapturedData();
$retiredCssResponse = $harness->route('/hook/JSLive/getCSS', 'instance=42');
assertWebhookRouting(
    $retiredCssResponse['output'] === '' && $harness->childMessages === [],
    'The retired Calendar CSS route must no longer bypass dynamic-route authentication.'
);

$harness->resetCapturedData();
$harness->setChildResponses([
    json_encode(
        [
            'InstanceID'     => '42',
            'Contend'        => '<div>fixture</div>',
            'lastModify'     => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'EnableCache'    => true,
            'EnableViewport' => true
        ],
        JSON_THROW_ON_ERROR
    )
]);
$contentResponse = $harness->route('/hook/JSLive/', 'instance=42&pw=synthetic-secret');
assertWebhookRouting($contentResponse['output'] === '<div>fixture</div>', 'The default hook changed its content response.');
assertWebhookRouting(count($harness->childMessages) === 1, 'The default hook must forward one child message.');
$contentMessage = decodeWebhookMessage($harness->childMessages[0]);
assertWebhookRouting(
    ($contentMessage['inner']['cmd'] ?? null) === 'getContend',
    'The historical default command spelling changed.'
);

$harness->resetCapturedData();
$harness->setDebugEnabled(true);
$longText = str_repeat('D', 17_000);
$debugResponse = $harness->route(
    '/hook/JSLive/getData',
    'instance=42&pw=synthetic-secret&title=Free+text',
    ['content' => $longText, 'Password' => 'synthetic-post-password']
);
assertWebhookRouting(
    $debugResponse['output'] === 'NO INSTANCE FOUND!' && $harness->responseStatusCodes === [404],
    'Debug changed the missing-child HTTP response.'
);
assertWebhookRouting($harness->debugMessages !== [], 'Enabled Debug must log the complete webhook request.');
$loggedRequest = json_decode($harness->debugMessages[0]['data'], true, 512, JSON_THROW_ON_ERROR);
assertWebhookRouting(
    ($loggedRequest['queryData']['title'] ?? null) === 'Free text'
        && ($loggedRequest['post']['content'] ?? null) === $longText,
    'Webhook Debug omitted or shortened request content.'
);
assertWebhookRouting(
    ($loggedRequest['queryData']['pw'] ?? null) === '***'
        && ($loggedRequest['post']['Password'] ?? null) === '***',
    'Webhook Debug exposed a known credential.'
);

echo "JSLive webhook routing contracts verified.\n";
