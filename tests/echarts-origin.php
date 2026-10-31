<?php

declare(strict_types=1);

class IPSModuleStrict
{
    public function __construct(protected int $InstanceID)
    {
    }
    public function ReadAttributeString(string $name): string
    {
        return $GLOBALS['origins'][$this->InstanceID] ?? '';
    }
    public function WriteAttributeString(string $name, string $value): void
    {
        $GLOBALS['origins'][$this->InstanceID] = $value;
        ++$GLOBALS['originWrites'];
    }
}
function IPS_SemaphoreEnter(string $name, int $timeout): bool
{
    ++$GLOBALS['locks'];
    return true;
}
function IPS_SemaphoreLeave(string $name): void
{
    --$GLOBALS['locks'];
}
function IPS_GetInstanceListByModuleID(string $id): array
{
    return array_keys($GLOBALS['splitters']);
}
function SymconJSLive_GetMigrationInstallationID(int $id): string
{
    return $GLOBALS['splitters'][$id]->GetMigrationInstallationID();
}
require_once __DIR__ . '/../SymconJSLive/module.php';
$GLOBALS['origins'] = [];
$GLOBALS['originWrites'] = 0;
$GLOBALS['locks'] = 0;
$GLOBALS['splitters'] = [12000 => new SymconJSLive(12000), 12001 => new SymconJSLive(12001)];
$initialize = new ReflectionMethod(SymconJSLive::class, 'InitializeMigrationIdentity');
foreach ($GLOBALS['splitters'] as $splitter) {
    $initialize->invoke($splitter);
}
$first = $GLOBALS['splitters'][12000]->GetMigrationInstallationID();
if ($first === '' || $first !== $GLOBALS['splitters'][12001]->GetMigrationInstallationID() || $GLOBALS['locks'] !== 0) {
    throw new RuntimeException('Splitters must share one persistent migration origin.');
}
foreach ($GLOBALS['splitters'] as $splitter) {
    $initialize->invoke($splitter);
}
if ($GLOBALS['originWrites'] !== 2) {
    throw new RuntimeException('Reinitialization changed the origin.');
}
$GLOBALS['origins'][12001] = '22222222-2222-4222-8222-222222222222';
try {
    $initialize->invoke($GLOBALS['splitters'][12000]);
    throw new LogicException('Conflict accepted.');
}
catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'Conflicting')) {
        throw $exception;
    }
}
if ($GLOBALS['originWrites'] !== 2 || $GLOBALS['locks'] !== 0) {
    throw new RuntimeException('Conflict overwrote origin or leaked lock.');
}
echo "Persistent origin across splitters, repeated initialization and conflict handling verified.\n";
