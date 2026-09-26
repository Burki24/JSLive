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

    /** @var list<string> */
    public array $childMessages = [];

    /** @var list<array{message: string, data: string}> */
    public array $debugMessages = [];

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
     * @return array{output: string, result: mixed}
     */
    public function route(string $scriptName, string $queryString, array $post = []): array
    {
        $previousServer = $_SERVER;
        $previousPost = $_POST;
        $_SERVER = [
            'SCRIPT_NAME'          => $scriptName,
            'QUERY_STRING'         => $queryString,
            'HTTP_ACCEPT_ENCODING' => ''
        ];
        $_POST = $post;

        ob_start();
        try {
            $result = $this->ProcessHookData();
            $output = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        } finally {
            $_SERVER = $previousServer;
            $_POST = $previousPost;
            header_remove();
        }

        return ['output' => (string) $output, 'result' => $result];
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
$deniedResponse = $harness->route('/hook/JSLive/getData', 'instance=42&pw=wrong-secret');
assertWebhookRouting($deniedResponse['output'] === '', 'A rejected password must not produce response data.');
assertWebhookRouting($harness->childMessages === [], 'A rejected password must not reach child modules.');

$harness->resetCapturedData();
$missingInstanceResponse = $harness->route('/hook/JSLive/getData', 'pw=synthetic-secret');
assertWebhookRouting($missingInstanceResponse['output'] === '', 'A missing instance must not produce response data.');
assertWebhookRouting($harness->childMessages === [], 'A missing instance must not reach child modules.');

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
            'InstanceID'    => '42',
            'Contend'       => '<div>fixture</div>',
            'lastModify'    => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'EnableCache'   => true,
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

echo "JSLive webhook routing contracts verified.\n";
