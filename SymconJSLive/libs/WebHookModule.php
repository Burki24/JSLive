<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/libs/helper/DataFlowHelper.php';
require_once dirname(__DIR__, 2) . '/libs/helper/DebugHelper.php';

class WebHookModule extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DataFlowHelper;
    use \Burki24\SymconModuleHelper\DebugHelper;

    private string $hook = '';

    public function __construct(int $InstanceID, string $hook)
    {
        parent::__construct($InstanceID);

        $this->hook = $hook;
    }

    public function Create(): void
    {

        //Never delete this line!
        parent::Create();

        $this->RegisterHook($this->hook);
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {

        //Never delete this line!
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

    }

    public function ApplyChanges(): void
    {

        //Never delete this line!
        parent::ApplyChanges();

    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData(): void
    {
        if ($this->ReadPropertyBoolean('Debug')) {
            $this->SendSafeDebug('WebHook', ['post' => $_POST], PHP_INT_MAX, ['pw']);
        }
    }

    protected function json_encode_advanced(array $arr, $sequential_keys = false, $quotes = false, $beautiful_json = true, $decimals = 2)
    {

        $output = $this->isAssoc($arr) ? '{' : '[';
        $count = 0;
        foreach ($arr as $key => $value) {

            if ($this->isAssoc($arr) || (!$this->isAssoc($arr) && $sequential_keys == true)) {
                $output .= ($quotes ? '"' : '') . $key . ($quotes ? '"' : '') . ' : ';
            }

            if (is_array($value)) {
                $output .= $this->json_encode_advanced($value, $sequential_keys, $quotes, $beautiful_json);
            }
            elseif (is_bool($value)) {
                $output .= ($value ? 'true' : 'false');
            }
            elseif (is_numeric($value)) {
                if (is_float($value)) {
                    $output .= number_format($value, $decimals, '.', '');
                }else {
                    $output .= $value;
                }
            }
            else {
                $output .= ($quotes || $beautiful_json ? '"' : '') . $value . ($quotes || $beautiful_json ? '"' : '');
            }

            if (++$count < count($arr)) {
                $output .= ', ';
            }
        }

        $output .= $this->isAssoc($arr) ? '}' : ']';

        return $output;
    }
    protected function HexToRGB(int $Hex)
    {
        $r = floor($Hex / 65536);
        $g = floor(($Hex - ($r * 65536)) / 256);
        $b = $Hex - ($g * 256) - ($r * 65536);

        return ['R' => $r, 'G' => $g, 'B' => $b];
    }

    // -------------------------------------------------------------------------
    protected function IsCompressionAllowed($mimeType)
    {
        return in_array($mimeType, [
            'text/plain',
            'text/html',
            'text/xml',
            'text/css',
            'text/javascript',
            'application/xml',
            'application/xhtml+xml',
            'application/rss+xml',
            'application/json',
            'application/json; charset=utf-8',
            'application/javascript',
            'application/x-javascript',
            'image/svg+xml'
        ]);
    }

    // -------------------------------------------------------------------------
    protected function GetMimeType($extension)
    {
        $knownMimeTypes = [
            'woff2' => 'font/woff2'
        ];
        $normalizedExtension = strtolower((string) $extension);
        if (array_key_exists($normalizedExtension, $knownMimeTypes)) {
            return $knownMimeTypes[$normalizedExtension];
        }

        $lines = file(IPS_GetKernelDirEx() . 'mime.types');
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $normalizedExtension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    private function isAssoc(array $arr)
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
