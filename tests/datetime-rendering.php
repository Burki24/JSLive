<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
        protected int $InstanceID;

        public function __construct(int $instanceID = 42)
        {
            $this->InstanceID = $instanceID;
        }

        public function ReadPropertyBoolean(string $name): bool
        {
            return false;
        }

        public function ReadPropertyInteger(string $name): int
        {
            return match ($name) {
                'Variable' => 9001,
                default    => 0
            };
        }

        public function ReadPropertyString(string $name): string
        {
            return $name === 'Template' ? 'DateTimePicker1' : '';
        }

        public function SendDebug(string $message, string $data, int $format): void
        {
        }
    }
}

if (!function_exists('IPS_GetConfiguration')) {
    function IPS_GetConfiguration(int $instanceID): string
    {
        return json_encode(
            [
                'Template'         => 'DateTimePicker1',
                'TemplateScriptID' => 0,
                'Variable'         => 9001
            ],
            JSON_THROW_ON_ERROR
        );
    }
}

if (!function_exists('IPS_VariableExists')) {
    function IPS_VariableExists(int $variableID): bool
    {
        return $variableID === 9001;
    }
}

if (!function_exists('GetValue')) {
    function GetValue(int $variableID): int
    {
        if ($variableID !== 9001) {
            throw new RuntimeException('Unexpected variable requested.');
        }

        return 1700000000;
    }
}

require_once dirname(__DIR__) . '/SymconJSLiveDateTimePicker/module.php';

final class DateTimePickerRenderingHarness extends SymconJSLiveDateTimePicker
{
    public function renderWebpage(): string
    {
        return $this->GetWebpage();
    }
}

$rendered = (new DateTimePickerRenderingHarness())->renderWebpage();

if (!str_contains($rendered, 'let value = 1700000000;')) {
    throw new RuntimeException('The integer timestamp was not rendered into the DateTimePicker template.');
}

foreach (['{CONFIG}', '{VALUE}', '{FONTS}'] as $placeholder) {
    if (str_contains($rendered, $placeholder)) {
        throw new RuntimeException('The DateTimePicker retained the placeholder ' . $placeholder . '.');
    }
}

echo "JSLive DateTimePicker rendering contracts verified.\n";
