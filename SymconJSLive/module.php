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
            ['Buffer' => json_encode($sendData, JSON_THROW_ON_ERROR)]
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

        if ($this->ReadPropertyBoolean('Debug')) {
            parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $debugQuery);
            $this->SendSafeDebug('WebHook', [
                'scriptName'    => $_SERVER['SCRIPT_NAME'] ?? null,
                'requestMethod' => $_SERVER['REQUEST_METHOD'] ?? null,
                'queryData'     => $debugQuery,
                'post'          => $_POST
            ], PHP_INT_MAX, ['pw']);
        }

        if (strpos($_SERVER['SCRIPT_NAME'], '/hook/JSLive/WS') !== false) {
            // The WS route has no response body; its request is already logged above when Debug is enabled.
        } elseif (strpos($_SERVER['SCRIPT_NAME'], '/hook/JSLive/js') !== false) {
            //get javascript files load from webhook
            $subpath = substr($_SERVER['SCRIPT_NAME'], strlen('/hook/JSLive/'));
            $path = __DIR__ . '/' . $subpath;

            if ($this->ReadPropertyBoolean('Debug'))
                $this->SendSafeDebug('WebHook', 'JS PATH =>' . $path);

            if (!file_exists($path)) {
                $this->SendPlainTextResponse(404, '');
                return;
            }

            header('HTTP/1.1 200 X');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            //http_response_code(200);
            $path_parts = pathinfo($path);
            $mimeType = $this->GetMimeType($path_parts['extension']);
            header('Content-Type: ' . $mimeType);

            if ($subpath == 'js/init.js') {
                $queryData = [];
                foreach (explode('&', $_SERVER['QUERY_STRING']) as $item) {
                    $pos2 = strpos($item, '=');
                    if ($pos2 !== false) {
                        $p_arr = explode('=', $item);
                        if (count($p_arr) > 2) continue;
                        $queryData[strtolower($p_arr[0])] = $p_arr[1];
                    }
                }

                $contend = file_get_contents($path);
                $contend = str_replace('{INSTANCEID}', $queryData['intid'], $contend);
                $contend = str_replace('{BOXID}', $queryData['boxid'], $contend);
                $contend = str_replace('{PW}', $queryData['pw'], $contend);
                $contend = str_replace('{LINK}', $queryData['link'], $contend);

                header('Content-Length: ' . strlen($contend));
                echo $contend;
                return;
            }

            if ($this->ReadPropertyBoolean('enableCache')) {
                //Add caching support
                $lastmodified = filemtime($path);
                header('Cache-Control: max-age=3600');
                header('Last-Modified: ' . $lastmodified);
                $etag = md5_file($path);
                header('ETag: ' . $etag);

                //CHeck if etag header exist and get them
                $Header_Etag = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

                if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastmodified || $Header_Etag == $etag) {
                    header('HTTP/1.1 304 Not Modified');
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
            $Type = substr($_SERVER['SCRIPT_NAME'], strlen('/hook/JSLive/'));
            if (empty($Type)) $Type = 'getContend';

            $queryData = [];

            foreach (explode('&', $_SERVER['QUERY_STRING']) as $item) {
                $pos2 = strpos($item, '=');
                if ($pos2 !== false) {
                    $p_arr = explode('=', $item);
                    if (count($p_arr) > 2) continue;
                    $queryData[strtolower($p_arr[0])] = $p_arr[1];
                }
            }

            //password prüfen!
            $passwordIsSet = $this->ReadPropertyString('Password');
            if (!empty($passwordIsSet)) {
                $passwordIsSet = urlencode($passwordIsSet);
                $password = '';
                if (array_key_exists('pw', $queryData)) {
                    $password = $queryData['pw'];
                }
                //Keinpassword bei CSS Abfrage!

                if ($passwordIsSet != $password && strtolower($Type) != 'getcss') {
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
                echo $configuration;
                return;
            }

            if (!array_key_exists('instance', $queryData)) {
                $this->SendSafeDebug('WebHook', 'INSTANCE NOT SET!');
                return ''; //wenn instance Parameter nicht gefunden
            }

            header('HTTP/1.1 200 X');
            header('Access-Control-Allow-Origin: *');

            $sendData = ['cmd' => $Type, 'instance' => $queryData['instance'], 'queryData' => $queryData];
            $contend = $this->SendDataToChildren($this->EncodeDataFlowMessage(
                '{79D59629-E9C5-44F1-0F34-0FBC5C88F307}',
                ['Buffer' => json_encode($sendData, JSON_THROW_ON_ERROR)]
            ));

            if (!is_array($contend) || count($contend) == 0) {
                $this->SendSafeDebug('WebHook-' . $Type, 'NO INSTANCE FOUND!');
                $this->SendSafeDebug('WebHook-' . $Type, [
                    'contentType'  => get_debug_type($contend),
                    'contentCount' => is_countable($contend) ? count($contend) : null
                ]);
                header('Content-Type: text/html');
                return 'NO INSTANCE FOUND!'; //wenn instance nicht gefunden
            }

            if (strtolower($Type) == 'getsvg') {
                header('Content-type: image/svg+xml');
                //header("Content-Type: text/html");
            }elseif (strtolower($Type) == 'exportconfiguration') {
                $date = new DateTime();
                header('Content-disposition: attachment; filename=' . IPS_GetInstance($queryData['instance'])['ModuleInfo']['ModuleName'] . '_' . IPS_GetObject($queryData['instance'])['ObjectName'] . '_ID' . $queryData['instance'] . '_' . $date->format('Y-m-d_H-i-s') . '.json');
                header('Content-type: application/json');
            }elseif (strtolower($Type) == 'loadfile') {
                //Here Do Nothing
            }elseif (strtolower($Type) == 'getfillimg') {
                //Here Do Nothing
            }elseif (strtolower($Type) == 'getCSS') {
                //Here Do Nothing
            }else {
                header('Content-Type: text/html');
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
                    echo 'Instance Not in List!';
                    return;
                }

                $contend = $arr_data['Contend'];
                $lastmodified = $arr_data['lastModify'];
                header('Content-Type: text/html');
                $useCache = true;

                if (!$arr_data['EnableCache']) {
                    //wenn cache deaktiviert dann global aktualiesieren!
                    $contend = $this->ReplacePlaceholder($contend, $queryData['instance'], $arr_data['EnableViewport']);
                }
            }
            elseif (strtolower($Type) == 'loadfile') {
                $arr_data = json_decode($contend[0], true);
                if (strtolower($arr_data['Type']) == 'css') {
                    header('Content-type: text/css');
                } else {
                    header('Content-type: text/javascript');
                }
                $contend = $arr_data['Contend'];
                $useCache = true;
            }elseif (strtolower($Type) == 'getfillimg') {
                $arr_data = json_decode($contend[0], true);
                header('Content-type: ' . $arr_data['Type']);
                $contend = base64_decode($arr_data['Contend']);
                $useCache = true;
            }elseif (strtolower($Type) == 'getcss') {
                $arr_data = [];

                foreach ($contend as $s_contend) {
                    $c_data = json_decode($contend[0], true);
                    if ($c_data['InstanceID'] == $queryData['instance']) {
                        $arr_data = $c_data;
                        break;
                    }
                }

                if (count($arr_data) == 0) {
                    $this->SendSafeDebug('WebHook-' . $Type, 'Instance Not in List! (getCSS)');
                    echo 'Instance Not in List!';
                    return;
                }

                $this->SendSafeDebug('TEST', ['lastModify' => $arr_data['lastModify']]);

                $contend = $arr_data['Contend'];
                $lastmodified = $arr_data['lastModify'];
                header('Content-type: text/css');
                $useCache = false; //cache ist aktuell verbuggt bei css
            }else {
                $contend = $contend[0];
            }

            if ($this->ReadPropertyBoolean('enableCache') && $useCache) {
                //Add caching support
                header('Cache-Control: max-age=3600');
                header('Last-Modified: ' . $lastmodified);
                $etag = md5($contend);
                header('ETag: ' . $etag);

                //CHeck if etag header exist and get them
                $Header_Etag = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

                if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastmodified || $Header_Etag == $etag) {
                    header('HTTP/1.1 304 Not Modified');
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

    private function ReplacePlaceholder(string $htmlData, int $IntID, bool $viewport)
    {
        $address = $this->ReadPropertyString('Address');

        $htmlData = str_replace('{GLOBAL}', $this->json_encode_advanced($this->GetConfigurationData()), $htmlData);
        $htmlData = str_replace('{ADDRESS}', $address, $htmlData);
        $htmlData = str_replace('{PASSWORD}', $this->ReadPropertyString('Password'), $htmlData);
        $htmlData = str_replace('{INSTANCE}', $IntID, $htmlData);

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
