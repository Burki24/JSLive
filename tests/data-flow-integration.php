<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/helper/DataFlowHelper.php';

use Burki24\SymconModuleHelper\DataFlowHelper;

final class DataFlowHarness
{
    use DataFlowHelper;

    /**
     * @param array<string, mixed> $payload
     */
    public function encode(string $dataID, array $payload): string
    {
        return $this->EncodeDataFlowMessage($dataID, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $json, string $dataID): array
    {
        return $this->DecodeDataFlowMessage($json, $dataID);
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertDataFlow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$parentDataID = '{751AABD7-E31D-024C-5CC0-82AC15B84095}';
$childDataID = '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}';
$harness = new DataFlowHarness();

$cases = [
    [$parentDataID, ['InstanceID' => 42, 'Type' => 'GetLink']],
    [$parentDataID, ['InstanceID' => 42, 'Type' => 'UpdateHtml', 'ViewPort' => true]],
    [$childDataID, ['cmd' => 'UpdateCache', 'instance' => 0]],
    [$childDataID, ['cmd' => 'getContend', 'instance' => '42', 'queryData' => ['instance' => '42']]],
];

foreach ($cases as [$dataID, $innerPayload]) {
    $buffer = json_encode($innerPayload, JSON_THROW_ON_ERROR);
    $encoded = $harness->encode($dataID, ['Buffer' => $buffer]);
    $decoded = $harness->decode($encoded, $dataID);

    assertDataFlow($decoded['DataID'] === $dataID, 'The data-flow identifier changed.');
    assertDataFlow($decoded['Buffer'] === $buffer, 'The inner Buffer JSON changed.');
    assertDataFlow(
        json_decode((string) $decoded['Buffer'], true, 512, JSON_THROW_ON_ERROR) === $innerPayload,
        'The decoded inner payload changed.'
    );
}

$splitterSource = file_get_contents(dirname(__DIR__) . '/SymconJSLive/module.php');
$webHookSource = file_get_contents(dirname(__DIR__) . '/SymconJSLive/libs/WebHookModule.php');
$moduleBaseSource = file_get_contents(dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php');
assertDataFlow($splitterSource !== false, 'Unable to read the splitter source.');
assertDataFlow($webHookSource !== false, 'Unable to read the webhook base source.');
assertDataFlow($moduleBaseSource !== false, 'Unable to read the child-module base source.');

assertDataFlow(
    str_contains($webHookSource, 'use \\Burki24\\SymconModuleHelper\\DataFlowHelper;'),
    'WebHookModule does not integrate DataFlowHelper.'
);
assertDataFlow(
    str_contains($moduleBaseSource, 'use \\Burki24\\SymconModuleHelper\\DataFlowHelper;'),
    'JSLiveModule does not integrate DataFlowHelper.'
);
assertDataFlow(
    !str_contains($splitterSource . $moduleBaseSource, 'utf8_encode('),
    'Deprecated utf8_encode() remains in a JSLive data-flow path.'
);
assertDataFlow(
    substr_count($splitterSource, 'EncodeDataFlowMessage(') === 2,
    'The splitter must keep its two child data-flow paths.'
);
assertDataFlow(
    substr_count($moduleBaseSource, 'EncodeDataFlowMessage(') === 5,
    'The child-module base must keep its five parent data-flow paths.'
);

echo "JSLive data-flow integration verified.\n";
