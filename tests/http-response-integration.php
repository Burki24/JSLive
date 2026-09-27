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
    substr_count($source, '$this->SendPlainTextResponse(') === 2,
    'JSLive must route its two safe early webhook responses through HttpResponseHelper.'
);

$helper = new HttpResponseIntegrationHarness();
ob_start();
$helper->sendPlainText(200, 'response body');
$output = ob_get_clean();

httpResponseIntegrationAssert($output === 'response body', 'Plain-text helper output must remain unchanged.');
httpResponseIntegrationAssert(http_response_code() === 200, 'Plain-text helper must set the requested status code.');
http_response_code(200);
header_remove();

fwrite(STDOUT, "JSLive HTTP response integration verified.\n");
