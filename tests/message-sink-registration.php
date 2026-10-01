<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        protected int $InstanceID;

        /** @var array<string, string> */
        protected array $buffers = [];

        /** @var list<array{senderID: int, message: int}> */
        protected array $registeredMessages = [];

        /** @var list<array{senderID: int, message: int}> */
        protected array $unregisteredMessages = [];

        public function __construct(int $instanceID = 42)
        {
            $this->InstanceID = $instanceID;
        }

        public function GetBufferList(): array
        {
            return array_keys($this->buffers);
        }

        public function GetBuffer(string $name): string
        {
            return $this->buffers[$name] ?? '';
        }

        public function SetBuffer(string $name, string $value): void
        {
            $this->buffers[$name] = $value;
        }

        public function RegisterMessage(int $senderID, int $message): void
        {
            $this->registeredMessages[] = compact('senderID', 'message');
        }

        public function UnregisterMessage(int $senderID, int $message): void
        {
            $this->unregisteredMessages[] = compact('senderID', 'message');
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }
    }
}

if (!function_exists('IPS_GetInstance')) {
    function IPS_GetInstance(int $instanceID): array
    {
        return ['ConnectionID' => 900];
    }
}

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';

final class MessageSinkRegistrationHarness extends JSLiveModule
{
    public function synchronize(array $variables): void
    {
        $this->UpdateMessageSink($variables);
    }

    public function seedVariables(array $variables): void
    {
        $this->SetBuffer('MessageSink', json_encode($variables, JSON_THROW_ON_ERROR));
    }

    /** @return list<array{senderID: int, message: int}> */
    public function registrations(): array
    {
        return $this->registeredMessages;
    }

    /** @return list<array{senderID: int, message: int}> */
    public function unregistrations(): array
    {
        return $this->unregisteredMessages;
    }

    public function storedVariables(): array
    {
        return json_decode($this->GetBuffer('MessageSink'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function resetMessageCalls(): void
    {
        $this->registeredMessages = [];
        $this->unregisteredMessages = [];
    }

    public function ReadPropertyBoolean(string $name): bool
    {
        return false;
    }
}

function assertMessageSinkRegistration(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$module = new MessageSinkRegistrationHarness();
$module->seedVariables([101, 102]);
$module->synchronize([101, 103]);

assertMessageSinkRegistration(
    $module->registrations() === [
        ['senderID' => 900, 'message' => 10503],
        ['senderID' => 103, 'message' => 10602],
        ['senderID' => 103, 'message' => 10603]
    ],
    'Only the gateway and newly added variables may be registered.'
);
assertMessageSinkRegistration(
    $module->unregistrations() === [
        ['senderID' => 102, 'message' => 10602],
        ['senderID' => 102, 'message' => 10603]
    ],
    'Removed variables must be unregistered from both update messages.'
);
assertMessageSinkRegistration(
    $module->storedVariables() === [101, 103],
    'The MessageSink buffer must contain the complete new variable list.'
);

$module->resetMessageCalls();
$module->synchronize([101, 103]);
assertMessageSinkRegistration(
    $module->registrations() === [['senderID' => 900, 'message' => 10503]],
    'An unchanged variable list must not register variable messages again.'
);
assertMessageSinkRegistration(
    $module->unregistrations() === [],
    'An unchanged variable list must not unregister variable messages.'
);

fwrite(STDOUT, "MessageSink registration behavior verified.\n");
