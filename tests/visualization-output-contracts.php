<?php

declare(strict_types=1);

// Process-local Symcon boundary; the child and splitter implementations are real.
define('VARIABLE_PRESENTATION_LEGACY', 'synthetic-legacy-presentation');

class IPSModuleStrict
{
    protected int $InstanceID;

    public function __construct(int $InstanceID)
    {
        $this->InstanceID = $InstanceID;
    }

    protected function ReadPropertyBoolean(string $name): bool
    {
        return $GLOBALS['outputContract']['properties'][$this->InstanceID][$name] ?? false;
    }

    protected function ReadPropertyInteger(string $name): int
    {
        return $GLOBALS['outputContract']['properties'][$this->InstanceID][$name] ?? 0;
    }

    protected function ReadPropertyString(string $name): string
    {
        return $GLOBALS['outputContract']['properties'][$this->InstanceID][$name] ?? '';
    }

    protected function GetBuffer(string $name): string
    {
        return $GLOBALS['outputContract']['buffers'][$name] ?? '';
    }

    protected function SetBuffer(string $name, string $value): void
    {
        $GLOBALS['outputContract']['buffers'][$name] = $value;
    }

    protected function Translate(string $value): string
    {
        return $value;
    }

    protected function RegisterVariableString(string $ident, string $name, array $presentation, int $position): int
    {
        $state = &$GLOBALS['outputContract'];
        $state['registrations'][] = [$ident, $name, $presentation, $position];
        // Model stable identifiers at the Symcon boundary, not module behavior.
        if (!isset($state['variables'][$ident])) {
            $state['variables'][$ident] = ['id' => $state['nextID']++, 'value' => '', 'hidden' => false];
        }
        return $state['variables'][$ident]['id'];
    }

    protected function UnregisterVariable(string $ident): void
    {
        $GLOBALS['outputContract']['removals'][] = $ident;
        unset($GLOBALS['outputContract']['variables'][$ident]);
    }

    protected function SetValue(string $ident, string $value): void
    {
        outputContractAssert(isset($GLOBALS['outputContract']['variables'][$ident]), 'Writing an unregistered variable: ' . $ident);
        $GLOBALS['outputContract']['variables'][$ident]['value'] = $value;
    }

    protected function SendDataToParent(string $json): string|false
    {
        $outer = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        outputContractAssert($outer['DataID'] === '{751AABD7-E31D-024C-5CC0-82AC15B84095}', 'Parent DataID changed.');
        $GLOBALS['outputContract']['messages'][] = json_decode($outer['Buffer'], true, 512, JSON_THROW_ON_ERROR);
        if ($GLOBALS['outputContract']['parentFailed']) {
            return false;
        }
        return $GLOBALS['outputContract']['splitter']->ForwardData($json);
    }
}

function IPS_GetInstance(int $id): array
{
    outputContractAssert($id === 42, 'Unexpected instance lookup.');
    return ['InstanceStatus' => $GLOBALS['outputContract']['status']];
}

function IPS_GetConfiguration(int $id): string
{
    return json_encode($GLOBALS['outputContract']['properties'][$id], JSON_THROW_ON_ERROR);
}

function IPS_GetObjectIDByIdent(string $ident, int $id): int|false
{
    outputContractAssert($id === 42, 'Unexpected variable owner.');
    return $GLOBALS['outputContract']['variables'][$ident]['id'] ?? false;
}

function IPS_SetHidden(int $id, bool $hidden): void
{
    foreach ($GLOBALS['outputContract']['variables'] as &$variable) {
        if ($variable['id'] === $id) {
            $variable['hidden'] = $hidden;
            return;
        }
    }
    throw new RuntimeException('Unknown variable in IPS_SetHidden.');
}

require_once dirname(__DIR__) . '/SymconJSLive/libs/JSLiveModule.php';
require_once dirname(__DIR__) . '/SymconJSLive/module.php';

class VisualizationOutputHarness extends JSLiveModule
{
    public string $html = '<html>{VIEWPORT}<main>{ADDRESS}|{INSTANCE}|{PASSWORD}</main></html>';

    public function refreshHtml(): void
    {
        $this->UpdateOutput();
    }

    public function refreshIframe(): void
    {
        $this->UpdateIframe();
    }

    public function readOutput(): array
    {
        return json_decode($this->GetOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function GetWebpage(): string
    {
        return $this->html;
    }
}

function outputContractAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function resetOutputContract(): VisualizationOutputHarness
{
    $GLOBALS['outputContract'] = [
        'properties' => [
            7 => ['Address'          => 'https://visualization.example', 'Password' => '', 'CreateIPSView' => true,
                'Iframe_useFullLink' => false, 'viewport_content' => 'width=device-width'],
            42 => ['CreateIPSView' => true, 'CreateOutput' => true, 'EnableCache' => true,
                'EnableViewport'   => true, 'IFrameHeight' => 0]
        ],
        'variables' => [], 'registrations' => [], 'removals' => [], 'buffers' => [],
        'messages'  => [], 'nextID' => 4200, 'status' => 102, 'parentFailed' => false,
        'splitter'  => new SymconJSLive(7)
    ];
    return new VisualizationOutputHarness(42);
}

// All three output switches are independent except for the two IPSView gates.
foreach ([false, true] as $splitterIPSView) {
    foreach ([false, true] as $childIPSView) {
        foreach ([false, true] as $createOutput) {
            $module = resetOutputContract();
            $state = &$GLOBALS['outputContract'];
            $state['properties'][7]['CreateIPSView'] = $splitterIPSView;
            $state['properties'][42]['CreateIPSView'] = $childIPSView;
            $state['properties'][42]['CreateOutput'] = $createOutput;
            $module->refreshHtml();
            outputContractAssert(!isset($state['variables']['Output']), 'UpdateOutput must not create the iframe variable.');
            $module->refreshIframe();
            outputContractAssert(isset($state['variables']['IPSView']) === ($splitterIPSView && $childIPSView), 'IPSView gate matrix changed.');
            outputContractAssert(isset($state['variables']['Output']) === $createOutput, 'CreateOutput must be independent of IPSView.');
            outputContractAssert($state['messages'][0] === [
                'Html' => $module->html, 'InstanceID' => 42, 'Type' => 'UpdateHtml', 'ViewPort' => true
            ], 'UpdateHtml payload changed.');
            $expectedHtml = '<html><meta name="viewport" content="width=device-width"><main>https://visualization.example|42|</main></html>';
            outputContractAssert($state['buffers']['Output'] === $expectedHtml, 'The HTML cache must contain splitter-processed content.');
            foreach ($state['registrations'] as [$ident, $name, $presentation, $position]) {
                outputContractAssert($name === $ident && $position === 0 && $presentation === [
                    'PRESENTATION' => VARIABLE_PRESENTATION_LEGACY, 'PROFILE' => '~HTMLBox'
                ], 'Legacy string variable presentation changed.');
            }
            if (isset($state['variables']['IPSView'])) {
                outputContractAssert($state['variables']['IPSView']['hidden'] === true, 'New IPSView must be hidden.');
                outputContractAssert($state['variables']['IPSView']['value'] === $expectedHtml, 'IPSView must store full processed HTML.');
            }
            if ($createOutput) {
                outputContractAssert($state['variables']['Output']['hidden'] === false, 'Output must not be hidden by module code.');
                outputContractAssert($state['variables']['Output']['value'] === '<iframe src="/hook/JSLive?Instance=42" frameborder="0" scrolling="no" style="width:100%;height:100%;"></iframe>', 'Zero-height iframe contract changed.');
            }
            $firstVariables = $state['variables'];
            $state['registrations'] = [];
            $module->refreshHtml();
            $module->refreshIframe();
            outputContractAssert($state['variables'] === $firstVariables && $state['removals'] === ($splitterIPSView && $childIPSView ? [] : ['IPSView', 'IPSView']), 'Repeated refresh must preserve enabled variables without removing/recreating them.');
            outputContractAssert(array_column($state['registrations'], 0) === ($createOutput ? ['Output'] : []), 'Existing IPSView must not be re-registered; Output remains registered by ident.');
        }
    }
}

// Link construction through the real child-to-splitter bridge.
$module = resetOutputContract();
$state = &$GLOBALS['outputContract'];
foreach (['' => '', 'synthetic +&/?' => '&pw=synthetic+%2B%26%2F%3F'] as $password => $suffix) {
    $state['properties'][7]['Password'] = $password;
    outputContractAssert($module->GetLink() === 'https://visualization.example/hook/JSLive?Instance=42' . $suffix, 'Absolute link changed.');
    outputContractAssert($state['messages'][array_key_last($state['messages'])] === ['InstanceID' => 42, 'Type' => 'GetLink'], 'GetLink payload changed.');
    foreach ([false, true] as $full) {
        $state['properties'][7]['Iframe_useFullLink'] = $full;
        outputContractAssert($module->GetLocalLink() === ($full ? 'https://visualization.example' : '') . '/hook/JSLive?Instance=42' . $suffix, 'Local/full link selection changed.');
    }
    foreach ([false, true] as $scripts) {
        outputContractAssert($module->GetConfigurationLink($scripts) === 'https://visualization.example/hook/JSLive/exportConfiguration?Instance=42' . $suffix . ($scripts ? '&scripts=1' : ''), 'Configuration export link changed.');
    }
}
$state['properties'][42]['IFrameHeight'] = 240;
$module->refreshIframe();
outputContractAssert($state['variables']['Output']['value'] === '<iframe src="https://visualization.example/hook/JSLive?Instance=42&pw=synthetic+%2B%26%2F%3F" width="100%" frameborder="0" scrolling="no" height="240"></iframe>', 'Fixed-height full-link iframe changed.');
$state['properties'][7]['Address'] = '';
outputContractAssert($module->GetLink() === 'No Address in Main modul Set!', 'Missing address message changed.');
outputContractAssert($module->GetConfigurationLink(false) === 'No Address in Main modul Set!', 'Missing export address message changed.');
outputContractAssert($module->GetConfigurationLink(true) === 'No Address in Main modul Set!&scripts=1', 'Legacy script suffix on missing address changed.');
outputContractAssert($module->GetLocalLink() === '/hook/JSLive?Instance=42&pw=synthetic+%2B%26%2F%3F', 'Empty full-link address must retain the existing relative fallback.');
$state['parentFailed'] = true;
outputContractAssert($module->GetLink() === '' && $module->GetLocalLink() === '' && $module->GetConfigurationLink(false) === '' && $module->GetConfigurationLink(true) === '', 'Failed parent link requests must return an empty string.');

// Refresh existing variables, then remove only the output disabled by its owner.
$module = resetOutputContract();
$state = &$GLOBALS['outputContract'];
$module->refreshHtml();
$module->refreshIframe();
$ipsViewID = $state['variables']['IPSView']['id'];
$outputID = $state['variables']['Output']['id'];
$state['properties'][42]['EnableCache'] = false;
$state['properties'][42]['EnableViewport'] = false;
$module->html = '<article>{VIEWPORT}updated {INSTANCE}</article>';
$module->refreshHtml();
outputContractAssert($state['variables']['IPSView']['id'] === $ipsViewID && $state['variables']['IPSView']['value'] === '<article>updated 42</article>', 'Existing IPSView content must refresh without changing its identity.');
outputContractAssert($state['buffers']['Output'] === '', 'Disabling cache must clear only the HTML buffer, not the Output variable.');
outputContractAssert($state['variables']['Output']['id'] === $outputID, 'HTML refresh must preserve the independent Output variable.');
$state['properties'][42]['CreateOutput'] = false;
$module->refreshIframe();
outputContractAssert(!isset($state['variables']['Output']) && isset($state['variables']['IPSView']), 'Disabling CreateOutput must preserve IPSView.');
$state['properties'][42]['CreateOutput'] = true;
$module->refreshIframe();
$state['properties'][7]['CreateIPSView'] = false;
$module->refreshHtml();
outputContractAssert(!isset($state['variables']['IPSView']) && isset($state['variables']['Output']), 'Disabling splitter IPSView must preserve Output.');
$state['properties'][7]['CreateIPSView'] = true;
$module->refreshHtml();
$state['properties'][42]['CreateIPSView'] = false;
$module->refreshHtml();
outputContractAssert(!isset($state['variables']['IPSView']) && isset($state['variables']['Output']), 'Disabling child IPSView must preserve Output.');
foreach ([101, 104, 200] as $inactiveStatus) {
    $state['status'] = $inactiveStatus;
    $before = $state;
    $module->refreshHtml();
    $module->refreshIframe();
    outputContractAssert($state === $before, 'An inactive child must not refresh buffers, variables or parent requests.');
}

// Cache miss creates both enabled variables; a hit does not rerender either.
$module = resetOutputContract();
$state = &$GLOBALS['outputContract'];
$result = $module->readOutput();
outputContractAssert(array_keys($result) === ['Contend', 'lastModify', 'EnableCache', 'EnableViewport', 'InstanceID'], 'Output JSON field names changed.');
outputContractAssert($result['Contend'] === $state['variables']['IPSView']['value'] && $result['EnableCache'] === true && $result['EnableViewport'] === true && $result['InstanceID'] === 42, 'Cache miss metadata/content changed.');
outputContractAssert($result['lastModify'] === $state['buffers']['LastModifed'] && preg_match('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/', $result['lastModify']) === 1, 'LastModifed buffer and HTTP date contract changed.');
outputContractAssert(array_column($state['messages'], 'Type') === ['UpdateHtml', 'GetLocalLink'], 'Cache miss must refresh HTML and iframe.');
$before = $state;
$module->html = '<article>uncached {INSTANCE}</article>';
outputContractAssert($module->readOutput() === $result && $state === $before, 'Cache hit must not regenerate output.');
$state['properties'][42]['EnableCache'] = false;
$before = $state;
$uncached = $module->readOutput();
outputContractAssert($uncached['Contend'] === '<article>uncached {INSTANCE}</article>' && $uncached['EnableCache'] === false && $state === $before, 'Uncached output must retain splitter placeholders and not refresh variables.');

echo "JSLive visualization output, IPSView and link contracts verified.\n";
