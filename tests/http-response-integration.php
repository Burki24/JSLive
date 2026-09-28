<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/helper/HttpResponseHelper.php';

use Burki24\SymconModuleHelper\HttpResponseHelper;

final class HttpResponseIntegrationHarness
{
    use HttpResponseHelper;

    public function sendPlainText(int $statusCode, string $message): void
    {
        $this->SendPlainTextResponse($statusCode, $message);
    }
}

function httpResponseIntegrationAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$source = file_get_contents(dirname(__DIR__) . '/SymconJSLive/module.php');
httpResponseIntegrationAssert($source !== false, 'JSLive source must be readable.');
httpResponseIntegrationAssert(
    str_contains($source, "require_once dirname(__DIR__) . '/libs/helper/HttpResponseHelper.php';"),
    'JSLive must load the vendored HttpResponseHelper.'
);
httpResponseIntegrationAssert(
    str_contains($source, 'use \\Burki24\\SymconModuleHelper\\HttpResponseHelper;'),
    'JSLive must use the HttpResponseHelper trait.'
);
httpResponseIntegrationAssert(
    substr_count($source, '$this->SendPlainTextResponse(') === 6,
    'JSLive must route all six plain-text webhook exits through HttpResponseHelper.'
);
httpResponseIntegrationAssert(
    !str_contains($source, "header('HTTP/1.1 200 X')"),
    'JSLive must not emit the obsolete custom HTTP 200 status line.'
);
httpResponseIntegrationAssert(
    substr_count($source, "header('X-Content-Type-Options: nosniff');") === 3,
    'JSLive must protect successful webhook response groups against MIME sniffing.'
);
httpResponseIntegrationAssert(
    substr_count($source, "header('Access-Control-Allow-Origin: *');") === 2,
    'JSLive must preserve wildcard CORS for static assets and dynamic module responses.'
);
httpResponseIntegrationAssert(
    str_contains($source, "header('Access-Control-Allow-Methods: POST, GET, OPTIONS');"),
    'JSLive must preserve its advertised static-asset CORS methods.'
);
httpResponseIntegrationAssert(
    substr_count($source, "header('Content-Type: application/json');") === 3,
    'JSLive must declare JSON content types for global, export and child JSON responses.'
);
httpResponseIntegrationAssert(
    str_contains($source, "'getconfiguration',")
        && str_contains($source, "'getdata',")
        && str_contains($source, "'getfeed',")
        && str_contains($source, "'getfonts',")
        && str_contains($source, "'getlanguage',")
        && str_contains($source, "'getupdate'"),
    'JSLive must classify all established child JSON commands as JSON responses.'
);
httpResponseIntegrationAssert(
    str_contains($source, "header('Content-Type: text/html; charset=utf-8');")
        && str_contains($source, "header('Content-Type: text/css; charset=utf-8');")
        && str_contains($source, "header('Content-Type: text/javascript; charset=utf-8');")
        && str_contains($source, "header('Content-Type: text/calendar; charset=utf-8');")
        && str_contains($source, "header('Content-Type: text/plain; charset=utf-8');")
        && str_contains($source, "header('Content-Type: image/svg+xml; charset=utf-8');"),
    'JSLive must declare explicit content types for established non-JSON text responses.'
);
httpResponseIntegrationAssert(
    str_contains($source, '$this->NormalizeImageMimeType($arr_data[\'Type\'] ?? null)'),
    'JSLive must normalize child-provided image MIME types before emitting a header.'
);
httpResponseIntegrationAssert(
    str_contains($source, "header('Content-Disposition: ' . \$this->BuildDownloadContentDisposition(\$filename));"),
    'JSLive must build the export Content-Disposition header from a sanitized filename.'
);

$helper = new HttpResponseIntegrationHarness();
foreach ([200 => '', 400 => 'bad request', 404 => 'not found'] as $statusCode => $body) {
    ob_start();
    $helper->sendPlainText($statusCode, $body);
    $output = ob_get_clean();

    httpResponseIntegrationAssert($output === $body, 'Plain-text helper output must remain unchanged.');
    httpResponseIntegrationAssert(
        http_response_code() === $statusCode,
        'Plain-text helper must set the requested status code.'
    );
    header_remove();
}
http_response_code(200);

fwrite(STDOUT, "JSLive HTTP response integration verified.\n");
