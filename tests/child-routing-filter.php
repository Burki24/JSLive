<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        public int $InstanceID;
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';

final class ChildRoutingFilterHarness extends JSLiveModule
{
    public function __construct(int $instanceID)
    {
        $this->InstanceID = $instanceID;
    }

    public function getFilter(): string
    {
        return $this->BuildReceiveDataFilter();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function encodeChildMessage(string $targetInstanceID, array $payload): string
    {
        return $this->EncodeDataFlowMessage(
            '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}',
            [
                'InstanceID' => $targetInstanceID,
                'Buffer'     => json_encode($payload, JSON_THROW_ON_ERROR)
            ]
        );
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertChildRoutingFilter(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$instanceID = 49951;
$harness = new ChildRoutingFilterHarness($instanceID);
$filter = '~' . $harness->getFilter() . '~';
$cases = [
    'own instance'                         => [(string) $instanceID, $instanceID, true],
    'broadcast'                            => ['0', 0, true],
    'foreign instance'                     => ['42', 42, false],
    'instance with same prefix'            => ['499510', 499510, false],
    'instance with same suffix'            => ['149951', 149951, false],
    'foreign route with matching inner ID' => ['42', $instanceID, false]
];

foreach ($cases as $label => [$targetInstanceID, $innerInstanceID, $expectedMatch]) {
    $message = $harness->encodeChildMessage(
        $targetInstanceID,
        ['cmd' => 'getContend', 'instance' => $innerInstanceID]
    );
    $match = preg_match($filter, $message);

    assertChildRoutingFilter($match !== false, 'Invalid receive-data filter: ' . preg_last_error_msg());
    assertChildRoutingFilter(
        ($match === 1) === $expectedMatch,
        sprintf('Unexpected receive-data filter result for %s.', $label)
    );
}

fwrite(STDOUT, "JSLive child routing filter verified.\n");
