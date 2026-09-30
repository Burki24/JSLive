<?php

declare(strict_types=1);
include_once __DIR__ . '/libs/WebHookModule.php';
require_once dirname(__DIR__) . '/libs/helper/DebugHelper.php';
require_once dirname(__DIR__) . '/libs/helper/HttpResponseHelper.php';

class SymconJSLive extends WebHookModule
{
    use \Burki24\SymconModuleHelper\DebugHelper;
    use \Burki24\SymconModuleHelper\HttpResponseHelper;

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID, 'JSLive');
    }
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Password', $this->GenerateRandomPassword());
        $this->RegisterPropertyString('Address', 'http://127.0.0.1:3777');

        $this->RegisterPropertyInteger('DataMode', 1);
        $this->RegisterPropertyInteger('RefreshTime', 3);

        //viewport
        $this->RegisterPropertyBoolean('EnableViewport', true);
        $this->RegisterPropertyString('viewport_content', 'width=device-width, initial-scale=1, maximum-scale=1.0, minimum-scale=1, user-scalable=no');

        $this->RegisterPropertyBoolean('CreateIPSView', true);

        //expert
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyBoolean('enableCache', false);
        $this->RegisterPropertyBoolean('enableCompression', true);
        $this->RegisterPropertyBoolean('Iframe_useFullLink', false);

        //da das direkte reinladen ja nicht geht!
        $this->LoadConnectAddress();
        $this->SetRandomPassword();
    }
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->SetStatus('102');

        //update all submoduls
        $sendData = ['cmd' => 'UpdateCache', 'instance' => 0];
        $this->SendDataToChildren($this->EncodeDataFlowMessage(
            '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}',
            [
                'InstanceID' => '0',
                'Buffer'     => json_encode($sendData, JSON_THROW_ON_ERROR)
            ]
        ));
    }

    public function ForwardData($JSONString)
    {
        $rData = json_decode($JSONString, true);
        $jsonData = json_decode($rData['Buffer'], true);

        if ($this->ReadPropertyBoolean('Debug')) {
            $this->SendSafeDebug('ForwardData', [
                'request' => $rData,
                'data'    => $jsonData
            ], PHP_INT_MAX, ['pw']);
        }

        switch ($jsonData['Type']) {
            case 'UpdateHtml':
                $IntID = $jsonData['InstanceID'];
                $Html = $jsonData['Html'];
                $ViewPort = $jsonData['ViewPort'];

                $output = $this->ReplacePlaceholder($Html, $IntID, $ViewPort);
                $ipsview = $this->ReadPropertyBoolean('CreateIPSView');
                return json_encode(['output' => $output, 'ipsview' => $ipsview]);
            case 'GetLink':
                $intId = $jsonData['InstanceID'];

                $link = $this->ReadPropertyString('Address');
                $pw = $this->ReadPropertyString('Password');

                if (empty($link)) {
                    return 'No Address in Main modul Set!';
                }

                if (empty($pw)) {
                    return $link . '/hook/JSLive?Instance=' . $intId;
                }else {
                    return $link . '/hook/JSLive?Instance=' . $intId . '&pw=' . urlencode($pw);
                }
                // No break. Add additional comment above this line if intentional
            case 'GetLocalLink':
                $intId = $jsonData['InstanceID'];
                $pw = $this->ReadPropertyString('Password');

                if ($this->ReadPropertyBoolean('Iframe_useFullLink')) {
                    $link = $this->ReadPropertyString('Address');
                    if (empty($pw)) {
                        return $link . '/hook/JSLive?Instance=' . $intId;
                    }else {
                        return $link . '/hook/JSLive?Instance=' . $intId . '&pw=' . urlencode($pw);
                    }
                }else {
                    if (empty($pw)) {
                        return '/hook/JSLive?Instance=' . $intId;
                    }else {
                        return '/hook/JSLive?Instance=' . $intId . '&pw=' . urlencode($pw);
                    }
                }
                // No break. Add additional comment above this line if intentional
            case 'GetConfigurationLink':
                $intId = $jsonData['InstanceID'];

                $link = $this->ReadPropertyString('Address');
                $pw = $this->ReadPropertyString('Password');

                if (empty($link)) {
                    return 'No Address in Main modul Set!';
                }

                if (empty($pw)) {
                    return $link . '/hook/JSLive/exportConfiguration?Instance=' . $intId;
                }else {
                    return $link . '/hook/JSLive/exportConfiguration?Instance=' . $intId . '&pw=' . urlencode($pw);
                }
                // No break. Add additional comment above this line if intentional
            case 'GetGlobalConfiguartion':
                $configuration = IPS_GetConfiguration($this->InstanceID);
                if ($this->ReadPropertyBoolean('Debug')) {
                    $this->SendSafeDebug('GetGlobalConfiguartion', json_decode($configuration, true), PHP_INT_MAX, ['pw']);
                }
                return $configuration;
                break;
        }
    }

    public function UpdateTemplates(int $category)
    {
        $templates = glob(__DIR__ . '/templates/*.html');
        //$category = $this->ReadPropertyInteger("TemplateCategoryID");

        if ($category == 0) {
            echo 'It is not allowed to use the standard directory as the template directory!';
        }

        if (!IPS_CategoryExists($category)) {
            echo 'The template directory does not seem to exist!';
        }

        foreach ($templates as $template_path) {
            $path_parts = pathinfo($template_path);
            $template_name = '(Default)' . $path_parts['filename'];

            $ScriptID = @IPS_GetScriptIDByName($template_name, $category);
            if ($ScriptID === false) {
                $ScriptID = IPS_CreateScript(0);
                IPS_SetParent($ScriptID, $category);
                IPS_SetName($ScriptID, $template_name);
            }

            IPS_SetScriptContent($ScriptID, file_get_contents($template_path));

            $this->SendSafeDebug('UpdateTemplates', 'FileName: ' . $path_parts['filename']);
        }

    }
    public function LoadConnectAddress(bool $start = false)
    {
        if (!$start || !empty($this->ReadPropertyString('Address'))) return;

        $confData = json_decode(IPS_GetConfiguration($this->InstanceID), true);

        //bestimmte aktuelle einstellungen beibehalten
        $confData['Address'] = $this->GetConnectAddress();

        IPS_SetConfiguration($this->InstanceID, json_encode($confData));
        IPS_ApplyChanges($this->InstanceID);
    }
    public function SetRandomPassword(bool $start = false, bool $override = false)
    {
        if (!$override && (!$start || !empty($this->ReadPropertyString('Password')))) return;

        $confData = json_decode(IPS_GetConfiguration($this->InstanceID), true);

        //bestimmte aktuelle einstellungen beibehalten
        $confData['Password'] = $this->GenerateRandomPassword();

        IPS_SetConfiguration($this->InstanceID, json_encode($confData));
        IPS_ApplyChanges($this->InstanceID);

        if ($override) echo 'New Password set: ' . $confData['Password'];
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $queryData = $this->ParseQueryString((string) ($_SERVER['QUERY_STRING'] ?? ''));

        if ($this->ReadPropertyBoolean('Debug')) {
            $this->SendSafeDebug('WebHook', [
                'scriptName'    => $scriptName,
                'requestMethod' => $_SERVER['REQUEST_METHOD'] ?? null,
                'queryData'     => $queryData,
                'post'          => $_POST
            ], PHP_INT_MAX, ['pw']);
        }

        if ($scriptName === '/hook/JSLive/WS' || str_starts_with($scriptName, '/hook/JSLive/WS/')) {
            // The WS route has no response body; its request is already logged above when Debug is enabled.
        } elseif ($scriptName === '/hook/JSLive/js' || str_starts_with($scriptName, '/hook/JSLive/js/')) {
            //get javascript files load from webhook
            $subpath = substr($scriptName, strlen('/hook/JSLive/'));
            $path = $this->ResolveStaticAssetPath($scriptName);

            if ($this->ReadPropertyBoolean('Debug'))
                $this->SendSafeDebug('WebHook', 'JS PATH =>' . $path);

            if ($path === null) {
                $this->SendPlainTextResponse(404, '');
                return;
            }

            http_response_code(200);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            header('X-Content-Type-Options: nosniff');
            $path_parts = pathinfo($path);
            $mimeType = $this->GetMimeType($path_parts['extension']);
            header('Content-Type: ' . $mimeType);

            if ($subpath == 'js/init.js') {
                $instanceID = filter_var(
                    $queryData['intid'] ?? null,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                if ($instanceID === false) {
                    $instanceID = 0;
                }

                $contend = file_get_contents($path);
                $contend = str_replace('{INSTANCEID}', (string) $instanceID, $contend);
                $contend = str_replace('{BOXID}', $this->EncodeJavaScriptString($queryData['boxid'] ?? ''), $contend);
                $contend = str_replace('{PW}', $this->EncodeJavaScriptString($queryData['pw'] ?? ''), $contend);
                $contend = str_replace('{LINK}', $this->EncodeJavaScriptString($queryData['link'] ?? ''), $contend);

                header('Content-Length: ' . strlen($contend));
                echo $contend;
                return;
            }

            if ($this->ReadPropertyBoolean('enableCache')) {
                //Add caching support
                $lastmodified = filemtime($path);
                header('Cache-Control: max-age=3600');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT');
                $etag = md5_file($path);
                header('ETag: ' . $etag);

                if ($this->IsNotModified($lastmodified, $etag)) {
                    http_response_code(304);
                    return;
                }
            }else {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
            }

            if ($this->ReadPropertyBoolean('enableCompression') && $this->IsCompressionAllowed($mimeType)) {
                $compressed = gzencode(file_get_contents($path));
                header('Content-Encoding: gzip');
                header('Content-Length: ' . strlen($compressed));
                echo $compressed;
            }else {
                header('Content-Length: ' . filesize($path));
                echo file_get_contents($path);
            }
        }else {
            //Daten vom Modul Laden
            $Type = substr($scriptName, strlen('/hook/JSLive/'));
            if (empty($Type)) $Type = 'getContend';

            //password prüfen!
            $passwordIsSet = $this->ReadPropertyString('Password');
            if (!empty($passwordIsSet)) {
                $password = $queryData['pw'] ?? '';

                if (!hash_equals($passwordIsSet, $password)) {
                    $this->SendSafeDebug('WebHook', 'WRONG PASSWORD!');
                    $this->SendPlainTextResponse(200, '');
                    $this->SendSafeDebug('WebHook', [
                        'password'           => $password,
                        'configuredPassword' => $passwordIsSet
                    ]);
                    return;
                }
            }

            //abrufen der Globalen Config
            if (strtolower($Type) == 'getglobalconfig') {
                $configuration = IPS_GetConfiguration($this->InstanceID);
                if ($this->ReadPropertyBoolean('Debug')) {
                    $this->SendSafeDebug('GetGlobalConfig', json_decode($configuration, true), PHP_INT_MAX, ['pw']);
                }
                http_response_code(200);
                header('Content-Type: application/json');
                header('Cache-Control: no-store, max-age=0');
                header('X-Content-Type-Options: nosniff');
                echo $configuration;
                return;
            }

            if (!array_key_exists('instance', $queryData)) {
                $this->SendSafeDebug('WebHook', 'INSTANCE NOT SET!');
                $this->SendPlainTextResponse(400, '');
                return;
            }

            http_response_code(200);
            header('Access-Control-Allow-Origin: *');
            header('X-Content-Type-Options: nosniff');

            $sendData = ['cmd' => $Type, 'instance' => $queryData['instance'], 'queryData' => $queryData];
            $contend = $this->SendDataToChildren($this->EncodeDataFlowMessage(
                '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}',
                [
                    'InstanceID' => $queryData['instance'],
                    'Buffer'     => json_encode($sendData, JSON_THROW_ON_ERROR)
                ]
            ));

            if (!is_array($contend) || count($contend) == 0) {
                $this->SendSafeDebug('WebHook-' . $Type, 'NO INSTANCE FOUND!');
                $this->SendSafeDebug('WebHook-' . $Type, [
                    'contentType'  => get_debug_type($contend),
                    'contentCount' => is_countable($contend) ? count($contend) : null
                ]);
                $this->SendPlainTextResponse(404, 'NO INSTANCE FOUND!');
                return;
            }

            if (strtolower($Type) == 'getsvg') {
                header('Content-Type: image/svg+xml; charset=utf-8');
                //header("Content-Type: text/html");
            }elseif (strtolower($Type) == 'exportconfiguration') {
                $date = new DateTime();
                $filename = IPS_GetInstance($queryData['instance'])['ModuleInfo']['ModuleName']
                    . '_' . IPS_GetObject($queryData['instance'])['ObjectName']
                    . '_ID' . $queryData['instance']
                    . '_' . $date->format('Y-m-d_H-i-s') . '.json';
                header('Content-Disposition: ' . $this->BuildDownloadContentDisposition($filename));
                header('Content-Type: application/json');
            }elseif (strtolower($Type) == 'loadfile') {
                //Here Do Nothing
            }elseif (strtolower($Type) == 'getfillimg') {
                //Here Do Nothing
            }elseif (strtolower($Type) == 'setdata') {
                header('Content-Type: text/plain; charset=utf-8');
            }elseif (in_array(strtolower($Type), [
                'getconfiguration',
                'getdata',
                'getfonts',
                'getlanguage',
                'getupdate'
            ], true)) {
                header('Content-Type: application/json');
            }else {
                header('Content-Type: text/html; charset=utf-8');
            }

            $lastmodified = gmdate('D, d M Y H:i:s', time()) . ' GMT';
            $useCache = false;

            $this->SendSafeDebug('WebHook-' . $Type, 'adaa');

            if (strtolower($Type) == 'getcontend') {
                $arr_data = [];

                foreach ($contend as $s_contend) {
                    $c_data = json_decode($contend[0], true);
                    if ($c_data['InstanceID'] == $queryData['instance']) {
                        $arr_data = $c_data;
                        break;
                    }
                }

                if (count($arr_data) == 0) {
                    $this->SendSafeDebug('WebHook-' . $Type, 'Instance Not in List!');
                    $this->SendPlainTextResponse(404, 'Instance Not in List!');
                    return;
                }

                $contend = $arr_data['Contend'];
                $lastmodified = $arr_data['lastModify'];
                header('Content-Type: text/html; charset=utf-8');
                $useCache = true;

                if (!$arr_data['EnableCache']) {
                    //wenn cache deaktiviert dann global aktualiesieren!
                    $contend = $this->ReplacePlaceholder($contend, (int) $queryData['instance'], $arr_data['EnableViewport']);
                }
            }
            elseif (strtolower($Type) == 'loadfile') {
                $arr_data = json_decode($contend[0], true);
                if (strtolower($arr_data['Type']) == 'css') {
                    header('Content-Type: text/css; charset=utf-8');
                } else {
                    header('Content-Type: text/javascript; charset=utf-8');
                }
                $contend = $arr_data['Contend'];
                $useCache = true;
            }elseif (strtolower($Type) == 'getfillimg') {
                $arr_data = json_decode($contend[0], true);
                header('Content-Type: ' . $this->NormalizeImageMimeType($arr_data['Type'] ?? null));
                $contend = base64_decode($arr_data['Contend']);
                $useCache = true;
            }else {
                $contend = $contend[0];
            }

            if ($this->ReadPropertyBoolean('enableCache') && $useCache) {
                //Add caching support
                header('Cache-Control: max-age=3600');
                header('Last-Modified: ' . $lastmodified);
                $etag = md5($contend);
                header('ETag: ' . $etag);

                $lastModifiedTimestamp = strtotime($lastmodified);
                if ($lastModifiedTimestamp !== false && $this->IsNotModified($lastModifiedTimestamp, $etag)) {
                    http_response_code(304);
                    return;
                }
            }

            if (!$useCache || !$this->ReadPropertyBoolean('enableCache')) {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
            }

            if ($this->ReadPropertyBoolean('Debug')) {
                $this->SendSafeDebug('WebHook-' . $Type, [
                    'contentType'   => get_debug_type($contend),
                    'contentLength' => is_string($contend) ? strlen($contend) : null
                ]);
            }

            if ($this->ReadPropertyBoolean('enableCompression') && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                $compressed = gzencode($contend);
                header('Content-Encoding: gzip');
                header('Content-Length: ' . strlen($compressed));
                echo $compressed;
            }else {
                header('Content-Length: ' . strlen($contend));
                echo $contend;
            }
        }
    }

    protected function NormalizeImageMimeType(mixed $mimeType): string
    {
        if (!is_string($mimeType)) {
            return 'application/octet-stream';
        }

        $mimeType = strtolower(trim($mimeType));

        return match ($mimeType) {
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/svg+xml' => $mimeType,
            default         => 'application/octet-stream'
        };
    }

    protected function BuildDownloadContentDisposition(string $filename): string
    {
        $filename = preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/', '_', $filename) ?? '';
        $filename = trim($filename, ' .');
        if ($filename === '') {
            $filename = 'JSLive-export.json';
        }

        $encodedFilename = json_encode($filename, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $filename = (string) json_decode($encodedFilename, true, 512, JSON_THROW_ON_ERROR);
        $asciiFilename = preg_replace('/[^\x20-\x7E]+/', '_', $filename) ?? 'JSLive-export.json';

        return 'attachment; filename="' . $asciiFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
    }

    private function IsNotModified(int $lastModified, string $etag): bool
    {
        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($ifNoneMatch !== '') {
            foreach (explode(',', $ifNoneMatch) as $candidate) {
                if (trim($candidate, " \t\"") === trim($etag, " \t\"")) {
                    return true;
                }
            }
        }

        $ifModifiedSince = trim((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? ''));
        if ($ifModifiedSince === '') {
            return false;
        }

        $ifModifiedSinceTimestamp = strtotime($ifModifiedSince);
        return $ifModifiedSinceTimestamp !== false && $ifModifiedSinceTimestamp >= $lastModified;
    }

    private function EncodeJavaScriptString(string $value): string
    {
        $encoded = json_encode(
            $value,
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
        );

        return substr($encoded, 1, -1);
    }

    /** @return array<string, string> */
    private function ParseQueryString(string $queryString): array
    {
        $queryData = [];

        foreach (explode('&', $queryString) as $item) {
            $separatorPosition = strpos($item, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $key = strtolower(urldecode(substr($item, 0, $separatorPosition)));
            if ($key === '') {
                continue;
            }

            $queryData[$key] = urldecode(substr($item, $separatorPosition + 1));
        }

        return $queryData;
    }

    private function ResolveStaticAssetPath(string $scriptName): ?string
    {
        $assetRoute = '/hook/JSLive/js/';
        if (!str_starts_with($scriptName, $assetRoute)) {
            return null;
        }

        $assetRoot = realpath(__DIR__ . '/js');
        $requestedPath = realpath(__DIR__ . '/' . substr($scriptName, strlen('/hook/JSLive/')));
        if ($assetRoot === false || $requestedPath === false || !is_file($requestedPath)) {
            return null;
        }

        $normalizedRoot = str_replace('\\', '/', $assetRoot);
        $normalizedPath = str_replace('\\', '/', $requestedPath);
        if (DIRECTORY_SEPARATOR === '\\') {
            $normalizedRoot = strtolower($normalizedRoot);
            $normalizedPath = strtolower($normalizedPath);
        }

        if (!str_starts_with($normalizedPath, rtrim($normalizedRoot, '/') . '/')) {
            return null;
        }

        return $requestedPath;
    }

    private function ReplacePlaceholder(string $htmlData, int $IntID, bool $viewport)
    {
        $address = $this->ReadPropertyString('Address');

        $htmlData = str_replace('{GLOBAL}', $this->json_encode_advanced($this->GetConfigurationData()), $htmlData);
        $htmlData = str_replace('{ADDRESS}', $address, $htmlData);
        $htmlData = str_replace('{PASSWORD}', $this->ReadPropertyString('Password'), $htmlData);
        $htmlData = str_replace('{INSTANCE}', (string) $IntID, $htmlData);

        if ($viewport) {
            $htmlData = str_replace('{VIEWPORT}', '<meta name="viewport" content="' . $this->ReadPropertyString('viewport_content') . '">', $htmlData);
        }else {
            $htmlData = str_replace('{VIEWPORT}', '', $htmlData);
        }

        return $htmlData;
    }

    private function GenerateRandomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = []; //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 12; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode('', $pass); //turn the array into a string
    }
    private function GetConfigurationData()
    {
        $output = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        $output['InstanceID'] = $this->InstanceID;

        return $output;
    }
    private static function isHttps(array $server)
    {
        if (array_key_exists('HTTPS', $server) && 'on' === $server['HTTPS']) {
            return true;
        }
        if (array_key_exists('SERVER_PORT', $server) && 443 === (int) $server['SERVER_PORT']) {
            return true;
        }
        if (array_key_exists('HTTP_X_FORWARDED_SSL', $server) && 'on' === $server['HTTP_X_FORWARDED_SSL']) {
            return true;
        }
        if (array_key_exists('HTTP_X_FORWARDED_PROTO', $server) && 'https' === $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }
        return false;
    }
    private function GetConnectAddress()
    {
        $connectID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
        if (count($connectID) == 0) {
        } return '';
        return CC_GetUrl($connectID);
    }
}
