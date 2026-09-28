<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveSyncModule/module.php';

function assertTlsHttpClient(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function invokeRejectedHttpRequest(object $module): array
{
    $method = new ReflectionMethod($module, 'GetWebData');
    $result = $method->invoke($module, 'http://127.0.0.1:9/not-allowed', []);
    assertTlsHttpClient(is_string($result), 'A rejected request must return a JSON error response.');

    $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
    assertTlsHttpClient(is_array($decoded), 'The request error response must be a JSON object.');

    return $decoded;
}

assertTlsHttpClient(function_exists('curl_init'), 'The PHP cURL extension is required for the HTTP client test.');

$fixtures = [
    'SyncModule' => [
        'module' => new SymconJSLiveModuleSync(),
        'source' => dirname(__DIR__) . '/SymconJSLiveSyncModule/module.php'
    ]
];

foreach ($fixtures as $name => $fixture) {
    $source = file_get_contents($fixture['source']);
    assertTlsHttpClient($source !== false, $name . ' source must be readable.');
    assertTlsHttpClient(
        preg_match('/CURLOPT_SSL_VERIFYPEER\s*=>\s*true/', $source) === 1
            && preg_match('/CURLOPT_SSL_VERIFYHOST\s*=>\s*2/', $source) === 1,
        $name . ' must verify the TLS certificate and host name.'
    );
    assertTlsHttpClient(
        preg_match('/CURLOPT_PROTOCOLS\s*=>\s*CURLPROTO_HTTPS/', $source) === 1
            && preg_match('/CURLOPT_REDIR_PROTOCOLS\s*=>\s*CURLPROTO_HTTPS/', $source) === 1,
        $name . ' must limit direct requests and redirects to HTTPS.'
    );
    assertTlsHttpClient(
        str_contains($source, 'CURLOPT_CONNECTTIMEOUT') && str_contains($source, 'CURLOPT_TIMEOUT'),
        $name . ' must bound connection and total request time.'
    );
    assertTlsHttpClient(
        !preg_match('/CURLOPT_SSL_VERIFYPEER\s*,\s*(?:0|false)/', $source),
        $name . ' must not disable TLS certificate verification.'
    );

    $failure = invokeRejectedHttpRequest($fixture['module']);
    assertTlsHttpClient(
        ($failure['success'] ?? null) === false && is_string($failure['msg'] ?? null) && $failure['msg'] !== '',
        $name . ' must return a controlled error for a rejected non-HTTPS request.'
    );
}

fwrite(STDOUT, "SyncModule TLS HTTP client verified.\n");
