<?php

declare(strict_types=1);

if (!class_exists('IPSModule')) {
    class IPSModule
    {
        /** @var list<array{message: string, data: string, format: int}> */
        public array $debug = [];

        /** @var array<string, string> */
        private array $buffers = [];

        public function SendDebug(string $message, string $data, int $format): void
        {
            $this->debug[] = compact('message', 'data', 'format');
        }

        protected function SetBuffer($name, $value)
        {
            $this->buffers[$name] = $value;
        }

        protected function GetBuffer($name)
        {
            return $this->buffers[$name] ?? '';
        }
    }
}

function IPS_GetInstanceListByModuleID(string $moduleID): array
{
    return [];
}

require_once dirname(__DIR__) . '/SymconJSLiveConfigStore/module.php';

class ConfigStoreDebugHarness extends SymconJSLiveConfigStore
{
    public function seedList(string $index, array $moduleList): void
    {
        $this->SetBuffer('SearchInstance', '');
        $this->SetBuffer('SelectInstanceType', $index);
        $this->SetBuffer('Modulelist', $moduleList);
    }

    public function getInstanceListForTest(): array
    {
        return (new ReflectionMethod(SymconJSLiveConfigStore::class, 'GetInstanceList'))->invoke($this);
    }
}

$module = new ConfigStoreDebugHarness();
$module->seedList('987654321', []);
if ($module->getInstanceListForTest() !== [] || count($module->debug) !== 1) {
    throw new RuntimeException('ConfigStore changed its invalid-selection behavior.');
}
if (str_contains($module->debug[0]['data'], '987654321')) {
    throw new RuntimeException('ConfigStore logged the supplied invalid selection.');
}

$module->debug = [];
$module->seedList('0', ['Example' => '{00000000-0000-0000-0000-000000000001}']);
if ($module->getInstanceListForTest() !== [] || $module->debug !== []) {
    throw new RuntimeException('ConfigStore changed its valid empty-list behavior.');
}

fwrite(STDOUT, "ConfigStore DebugHelper integration verified.\n");
