<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        protected $InstanceID;

        private array $buffers = [];

        public function __construct(int $instanceID)
        {
            $this->InstanceID = $instanceID;
        }

        public function ReadPropertyString(string $name): string
        {
            return (string) ($GLOBALS['jsliveConfigStoreState']['properties'][$name] ?? '');
        }

        protected function SetBuffer($name, $value)
        {
            $this->buffers[$name] = $value;
        }

        protected function GetBuffer($name)
        {
            return (string) ($this->buffers[$name] ?? '');
        }
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveConfigStore/module.php';

class TestableSymconJSLiveConfigStore extends SymconJSLiveConfigStore
{
    public function writeBufferValue(string $name, mixed $value): void
    {
        $this->SetBuffer($name, $value);
    }

    public function readBufferValue(string $name): mixed
    {
        return $this->GetBuffer($name);
    }
}

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertConfigStore(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$GLOBALS['jsliveConfigStoreState'] = [
    'properties' => [
        'UserID' => 'existing-user-id'
    ]
];

$module = new TestableSymconJSLiveConfigStore(42);

assertConfigStore(
    $module->ReadUserID() === 'existing-user-id',
    'The configured store user ID must be returned unchanged.'
);
assertConfigStore(
    $module->GetForumUser('MixedCaseUser') === 'https://community.symcon.de/u/mixedcaseuser',
    'Forum profile links must retain their established lowercase username format.'
);

$uuidMethod = new ReflectionMethod(SymconJSLiveConfigStore::class, 'GetUserID');
$uuid = $uuidMethod->invoke($module, pack('C*', ...range(0, 15)));
assertConfigStore(
    $uuid === '00010203-0405-4607-8809-0a0b0c0d0e0f',
    'UUID generation must retain its established version and variant bits.'
);

$bufferValue = [
    'nested' => ['value' => 7],
    'flag'   => true
];
$module->writeBufferValue('Example', $bufferValue);
assertConfigStore(
    $module->readBufferValue('Example') === $bufferValue,
    'Structured ConfigStore buffer values must survive serialization unchanged.'
);

echo "JSLive ConfigStore contracts verified.\n";
