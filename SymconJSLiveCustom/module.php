<?php

declare(strict_types=1);
include_once __DIR__ . '/../SymconJSLive/libs/JSLiveModule.php';

class SymconJSLiveCustom extends JSLiveModule
{
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyInteger('TemplateScriptID', 0);

        //Expert
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyInteger('ViewLevel', 0);
        $this->RegisterPropertyBoolean('EnableCache', true);
        $this->RegisterPropertyBoolean('CreateOutput', true);
        $this->RegisterPropertyBoolean('CreateIPSView', true);
        $this->RegisterPropertyInteger('DataUpdateRate', 50);
        $this->RegisterPropertyBoolean('EnableViewport', true);
        $this->RegisterPropertyInteger('IFrameHeight', 0);
        $this->RegisterPropertyInteger('overrideWidth', 0);
        $this->RegisterPropertyInteger('overrideHeight', 0);

        //colors
        $this->RegisterPropertyInteger('style_backgroundColor', 0);
        $this->RegisterPropertyFloat('style_backgroundColor_Alpha', 0);
        $this->RegisterPropertyInteger('style_highlightColor1', 0);
        $this->RegisterPropertyFloat('style_highlightColor1_Alpha', 1);
        $this->RegisterPropertyInteger('style_highlightColor2', 0);
        $this->RegisterPropertyFloat('style_highlightColor2_Alpha', 1);
        $this->RegisterPropertyInteger('style_highlightColor3', 0);
        $this->RegisterPropertyFloat('style_highlightColor3_Alpha', 1);
        $this->RegisterPropertyInteger('style_highlightColor4', 0);
        $this->RegisterPropertyFloat('style_highlightColor4_Alpha', 1);
        $this->RegisterPropertyInteger('style_highlightColor5', 0);
        $this->RegisterPropertyFloat('style_highlightColor5_Alpha', 1);

        //fonts
        $this->RegisterPropertyInteger('style_fontSize', 12);
        $this->RegisterPropertyInteger('style_fontColor', 0);
        $this->RegisterPropertyString('style_fontFamily', '');

        //border
        $this->RegisterPropertyInteger('style_borderRadius', 10);
        $this->RegisterPropertyInteger('style_borderWidth', 2);
        $this->RegisterPropertyInteger('style_borderColor', 0);

        $this->RegisterPropertyString('Datasets', '[]');
        $this->RegisterPropertyString('Libraries', '[]');
    }
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

    }

    public function ReceiveData(string $JSONString): string
    {
        parent::ReceiveData($JSONString);
        $jsonData = json_decode($JSONString, true);
        $buffer = json_decode($jsonData['Buffer'], true);

        //if($buffer["instance"] != $this->InstanceID) return;

        switch ($buffer['cmd']) {
            case 'exportConfiguration':
                return $this->ExportConfiguration($buffer['queryData']);
            case 'getContend':
                return $this->GetOutput();
            case 'getData':
                return $this->GetData($buffer['queryData']);
            case 'setData':
                return $this->SetData($buffer['queryData']);
            case 'loadFile':
                return json_encode($this->LoadFile($buffer['queryData']), JSON_THROW_ON_ERROR);
            default:
                if ($buffer['cmd'] != 'UpdateCache')
                    $this->SendSafeDebug('ReceiveData', 'ACTION FOR THIS MODULE NOT DEFINED!');
                break;
        }

        return '';
    }

    public function LoadOtherConfiguration(int $id): mixed
    {
        if (!IPS_ObjectExists($id)) return 'Instance/Chart not found!';

        if (IPS_GetObject($id)['ObjectType'] == 1) {
            //instance
            $intData = IPS_GetInstance($id);
            if ($intData['ModuleInfo']['ModuleID'] != IPS_GetInstance($this->InstanceID)['ModuleInfo']['ModuleID']) return 'Only Allowed at the same Modul!';

            $confData = json_decode(IPS_GetConfiguration($id), true);
            if ($this->ReadPropertyBoolean('Debug')) {
                $this->SendSafeDebug('LoadOtherConfiguration', $confData, PHP_INT_MAX, ['pw']);
            }

            IPS_SetConfiguration($this->InstanceID, json_encode($confData));
            IPS_ApplyChanges($this->InstanceID);
        }else return 'A Instance must be selected!';

        return null;
    }
    public function GenerateDefaultScript(): mixed
    {
        $templateID = $this->ReadPropertyInteger('TemplateScriptID');

        if ($templateID > 0 && IPS_ObjectExists($templateID)) {
            return 'Default Script can only create if no TemplateScriptID is set!';
        }

        $templateID = IPS_CreateScript(0);
        IPS_SetParent($templateID, $this->InstanceID);
        IPS_SetName($templateID, 'Default');
        $scriptData = file_get_contents(__DIR__ . '/../SymconJSLive/templates/Default.html');
        IPS_SetScriptContent($templateID, $scriptData);

        $confData = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        $confData['TemplateScriptID'] = $templateID;
        IPS_SetConfiguration($this->InstanceID, json_encode($confData));
        IPS_ApplyChanges($this->InstanceID);

        return null;
    }
    protected function GetWebpage()
    {
        $scriptID = $this->ReadPropertyInteger('TemplateScriptID');
        if (empty($scriptID)) {
            if ($this->ReadPropertyBoolean('Debug'))
                $this->SendSafeDebug('GetWebpage', 'NO TEMPLATE DEFINE!');

            $scriptData = file_get_contents(__DIR__ . '/../SymconJSLive/templates/Default.html');
            //return 'NO TEMPLATE DEFINE!';
        }else {
            if (!IPS_ScriptExists($scriptID)) {
                $this->SendSafeDebug('GetWebpage', 'Template NOT FOUND!');
                return '';
            }

            $scriptData = IPS_GetScriptContent($scriptID);
            if ($scriptData == '') {
                $this->SendSafeDebug('GetWebpage', 'Template IS EMPTY!');
            }
        }

        $scriptData = $this->ReplacePlaceholder($scriptData);

        return $scriptData;
    }
    private function GetData($querydata)
    {
        $datasets = json_decode($this->ReadPropertyString('Datasets'), true);
        $output = [];

        if (array_key_exists('var', $querydata)) {
            $var = $querydata['var'];

            //check variable is set in Instance!!
            //recursiv serach
            foreach ($datasets as $data) {
                if ($data['Object'] == 0 || !IPS_ObjectExists($data['Object'])) continue;

                if ($data['Object'] == $var) return [$this->GetSingelData($data['Object'])];

                $obj_data = IPS_GetObject($data['Object']);
                if ($obj_data['ObjectType'] == 0 || $obj_data['ObjectType'] == 1 && $obj_data['HasChildren']) {
                    foreach ($obj_data['ChildrenIDs'] as $item) {
                        if ($item == $var) {
                            //wenn variable in Childids dann ausgabe
                            return json_encode([$this->GetSingelData($item, true)], JSON_THROW_ON_ERROR);
                        }
                    }
                }
            }

        }else {
            foreach ($datasets as $data) {
                if ($data['Object'] == 0 || !IPS_ObjectExists($data['Object'])) continue;

                $obj_data = IPS_GetObject($data['Object']);
                if ($obj_data['ObjectType'] == 0 || $obj_data['ObjectType'] == 1 && $obj_data['HasChildren']) {
                    foreach ($obj_data['ChildrenIDs'] as $item) {
                        $erg = $this->GetSingelData($item, true);
                        if (count($erg) > 0)
                            $output[] = $erg;
                    }
                }else {
                    $erg = $this->GetSingelData($data['Object']);
                    if (count($erg) > 0)
                        $output[] = $erg;
                }
            }
        }
        return json_encode($output, JSON_THROW_ON_ERROR);
    }
    private function LoadFile($querydata)
    {
        if (!array_key_exists('ident', $querydata)) {
            $this->SendSafeDebug('LoadFile', 'NO IDENT SET!');
            return ['Contend' => '', 'Type' => ''];
        }

        $Libraries = json_decode($this->ReadPropertyString('Libraries'), true);
        $key = array_search($querydata['ident'], array_column($Libraries, 'Ident'));

        if ($key === false) {
            $this->SendSafeDebug('LoadFile', 'NO IDENT SET!');
            return ['Contend' => '', 'Type' => ''];
        }

        $item = $Libraries[$key];

        if (empty($item['Script']) && empty($item['File'])) {
            $this->SendSafeDebug('LoadFile', 'NO SCRIPT OR FILE SET!');
            return ['Contend' => '', 'Type' => ''];
        }

        if ($item['Script'] > 0 && IPS_ScriptExists($item['Script'])) {
            return ['Contend' => IPS_GetScriptContent($item['Script']), 'Type' => $item['Type']];
        }else {
            $this->SendSafeDebug('LoadFile', 'Embedded file loaded');
            return ['Contend' => base64_decode($item['File']), 'Type' => $item['Type']];
        }
    }

    private function GetSingelData(int $item, bool $isSubItem = false)
    {
        $obj_data2 = IPS_GetObject($item);
        switch ($obj_data2['ObjectType']) {
            case 2:
                //Variable
                //nur ausgeben wenn nicht hidden/disabled!
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    return ['Variable' => $item, 'Value' => GetValue($item)];
                }
                break;
            case 3:
                //Skript
                if (!$isSubItem && !$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled']) {
                    return ['Variable' => $item, 'Value' => IPS_RunScriptWait($item)];
                }
                break;
            case 5:
                //Media
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    return ['Variable' => $item, 'Value' => IPS_GetMediaContent($item)];
                }
                break;
            case 6:
                //Link
                //nur ausgeben wenn nicht hidden/disabled!
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    $l_data = IPS_GetLink($item);
                    return ['Variable' => $item, 'Value' => GetValue($l_data['TargetID'])];
                }
                break;
            default:
                return [];
        }
    }
    private function GetAllData()
    {
        $output = [];
        $datasets = json_decode($this->ReadPropertyString('Datasets'), true);

        foreach ($datasets as $item) {
            $s_output = [];
            $s_output['Title'] = $item['Title'];

            if (is_numeric($item['HighlightColor1']) && $item['HighlightColor1'] >= 0) {
                $rgbdata = $this->HexToRGB($item['HighlightColor1']);
                $s_output['HighlightColor1'] = 'rgba(' . $rgbdata['R'] . ', ' . $rgbdata['G'] . ', ' . $rgbdata['B'] . ', ' . number_format((float) $item['HighlightColor1_Alpha'], 2, '.', '') . ')';
            }else {
                $s_output['HighlightColor1'] = 'rgba(0,0,0,0)';
            }

            if (is_numeric($item['HighlightColor2']) && $item['HighlightColor2'] >= 0) {
                $rgbdata = $this->HexToRGB($item['HighlightColor2']);
                $s_output['HighlightColor2'] = 'rgba(' . $rgbdata['R'] . ', ' . $rgbdata['G'] . ', ' . $rgbdata['B'] . ', ' . number_format((float) $item['HighlightColor2_Alpha'], 2, '.', '') . ')';
            }else {
                $s_output['HighlightColor2'] = 'rgba(0,0,0,0)';
            }

            $obj_name = 'Object';
            if ($item[$obj_name] > 0 && IPS_ObjectExists($item[$obj_name])) {
                $s_output[$obj_name] = $this->LoadDataFromObject($item[$obj_name], true);
            }else {
                $s_output[$obj_name] = [];
            }

            $output[] = $s_output;
        }

        return json_encode($output, JSON_THROW_ON_ERROR);
    }
    private function LoadDataFromObject(int $obj_id, bool $onStart = false)
    {
        $output = [];
        $obj_data = IPS_GetObject($obj_id);

        switch ($obj_data['ObjectType']) {
            case 0:
            case 1:
                //Nur eine Ebene, keine ausgeblendetetn und keine
                if ($onStart && !$obj_data['ObjectIsHidden'] && !$obj_data['ObjectIsDisabled']) {
                    //Kategorie
                    //Instance
                    $output['ObjectID'] = $obj_id;
                    $output['ObjectType'] = $obj_data['ObjectType'];
                    $output['ObjectName'] = $obj_data['ObjectName'];
                    $output['HasChildren'] = $obj_data['HasChildren'];

                    $items = [];
                    foreach ($obj_data['ChildrenIDs'] as $item) {
                        $s_item = $this->LoadDataFromObject($item);

                        if (count($s_item) > 0)
                            $items[] = $s_item;
                    }
                    $output['ChildrenIDs'] = $items;
                }
                break;
            case 2:
                //Variable
                //nur ausgeben bei start, oder wenn nicht hidden/disabled!
                if ($onStart || (!$obj_data['ObjectIsHidden'] && !$obj_data['ObjectIsDisabled'])) {
                    $output['ObjectID'] = $obj_id;
                    $output['ObjectType'] = $obj_data['ObjectType'];
                    $output['ObjectName'] = $obj_data['ObjectName'];

                    $var_data = IPS_GetVariable($obj_id);
                    $output['VariableChanged'] = $var_data['VariableChanged'];
                    $output['VariableUpdated'] = $var_data['VariableUpdated'];
                    $output['VariableType'] = $var_data['VariableType'];

                    //profildata
                    $profil = [];
                    $profilname = $var_data['VariableProfile'];
                    if (!empty($var_data['VariableCustomProfile'])) $profilname = $var_data['VariableCustomProfile'];

                    if (IPS_VariableProfileExists($profilname)) {
                        $p_item = IPS_GetVariableProfile($profilname);

                        $profil['ProfileName'] = $p_item['ProfileName'];
                        $profil['MinValue'] = $p_item['MinValue'];
                        $profil['MaxValue'] = $p_item['MaxValue'];
                        $profil['Digits'] = $p_item['Digits'];
                        $profil['Prefix'] = $p_item['Prefix'];
                        $profil['Suffix'] = $p_item['Suffix'];
                    }

                    $output['VariableProfile'] = $profil;

                    $output['Value'] = GetValue($obj_id);
                }
                break;
            case 3:
                //Run only direct Set Skripts!!
                if ($onStart) {
                    //Skript
                    $output['ObjectID'] = $obj_id;
                    $output['ObjectType'] = $obj_data['ObjectType'];
                    $output['ObjectName'] = $obj_data['ObjectName'];

                    $output['Value'] = IPS_RunScriptWait($obj_id);
                }
                break;
            case 5:
                //Media
                //nur ausgeben bei start, oder wenn nicht hidden/disabled!
                if ($onStart || (!$obj_data['ObjectIsHidden'] && !$obj_data['ObjectIsDisabled'])) {
                    $output['ObjectID'] = $obj_id;
                    $output['ObjectType'] = $obj_data['ObjectType'];
                    $output['ObjectName'] = $obj_data['ObjectName'];

                    $output['Value'] = IPS_GetMediaContent($obj_id);
                }
                break;
            case 6:
                //Link
                //nur ausgeben bei start, oder wenn nicht hidden/disabled!
                if ($onStart || (!$obj_data['ObjectIsHidden'] && !$obj_data['ObjectIsDisabled'])) {
                    $l_data = IPS_GetLink($obj_id);
                    $output = $this->LoadDataFromObject($l_data['TargetID'], $onStart);
                }
                break;
            case 4:
            default:
                //Ergenis
                //Default

                //ignorieren
                break;
        }

        $this->SendSafeDebug('LoadDataFromObject', 'Object data loaded');
        return $output;
    }

    private function SetData(array $querydata)
    {
        if (!array_key_exists('obj', $querydata)) {
            $this->SendSafeDebug('SetData', 'NO OBJECT SET!');
            return 'NO OBJECT SET!';
        }
        if (!array_key_exists('val', $querydata)) {
            $this->SendSafeDebug('SetData', 'NO VALUE SET!');
            return 'NO VALUE SET!';
        }

        $obj = (int) $querydata['obj'];
        $val = $querydata['val'];

        if ($obj == 0 || !IPS_ObjectExists($obj)) {
            $this->SendSafeDebug('SetData', 'OBJECT NOT EXIST!');
            return 'OBJECT NOT EXIST!';
        }

        //check variable is set in Instance!!
        //recursiv serach
        $datasets = json_decode($this->ReadPropertyString('Datasets'), true);
        foreach ($datasets as $data) {
            if ($data['Object'] == 0 || !IPS_ObjectExists($data['Object'])) continue;

            //Main object
            if ($data['Object'] == $obj) {
                if ($data['ReadOnly']) {
                    $this->SendSafeDebug('SetData', 'Variable IS READONLY!');
                    return 'ACCESS DENIED';
                }else {
                    return $this->SetSingleData($obj, $val);
                }
            }

            $obj_data = IPS_GetObject($data['Object']);
            if ($obj_data['ObjectType'] == 0 || $obj_data['ObjectType'] == 1 && $obj_data['HasChildren']) {
                foreach ($obj_data['ChildrenIDs'] as $item) {
                    if ($item == $obj) {
                        if ($data['ReadOnly']) {
                            $this->SendSafeDebug('SetData', 'Variable IS READONLY!');
                            return 'ACCESS DENIED';
                        }else {
                            //wenn variable in Childids dann ausgabe
                            return $this->SetSingleData($obj, $val, true);
                        }
                    }
                }
            }
        }
        $this->SendSafeDebug('SetData', 'OBJECT NOT IN INSTANCE!');
        return 'ERROR';
    }
    private function SetSingleData(int $item, $val, bool $isSubItem = false)
    {
        $obj_data2 = IPS_GetObject($item);
        switch ($obj_data2['ObjectType']) {
            case 2:
                //Variable
                //nur ausgeben wenn nicht hidden/disabled!
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    $this->SendSafeDebug('SetSingleData', 'Update Variable');
                    SetValue($item, $val);
                    return 'OK';
                }
                break;
            case 3:
                //Skript
                if (!$isSubItem && !$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled']) {
                    $this->SendSafeDebug('SetSingleData', 'Run Script');
                    return IPS_RunScriptWaitEx($item, json_decode($val, true));
                }
                break;
            case 5:
                //Media
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    $this->SendSafeDebug('SetSingleData', 'Update Media');
                    IPS_SetMediaContent($item, $val);
                    return 'OK';
                }
                break;
            case 6:
                //Link
                //nur ausgeben wenn nicht hidden/disabled!
                if ((!$obj_data2['ObjectIsHidden'] && !$obj_data2['ObjectIsDisabled'])) {
                    $l_data = IPS_GetLink($item);
                    $this->SendSafeDebug('SetSingleData', 'Link resolved');
                    return $this->SetSingleData($l_data['TargetID'], $val, $isSubItem);
                }
                break;
            default:
                return 'WRONG TYPE!';
        }
    }

    private function ReplacePlaceholder(string $htmlData)
    {
        //configuration Data
        $htmlData = str_replace('{CONFIG}', $this->json_encode_advanced($this->GetConfigurationData()), $htmlData);

        //Value
        $htmlData = str_replace('{DATASETS}', $this->GetAllData(), $htmlData);

        //Load Fonts
        $htmlData = str_replace('{FONTS}', $this->LoadFonts(), $htmlData);

        //Load Libarys
        $arr = $this->LoadLibraries();
        $htmlData = str_replace('{SCRIPTS}', $arr['js'], $htmlData);
        $htmlData = str_replace('{CSS}', $arr['css'], $htmlData);

        return $htmlData;
    }
    private function GetConfigurationData()
    {
        $output = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        $output['InstanceID'] = $this->InstanceID;

        //alle colorvariablen umwandeln!
        foreach ($output as $key => $val) {
            $pos = strpos(strtolower($key), 'color');
            $pos2 = strpos(strtolower($key), 'alpha');
            if ($pos !== false && $pos2 === false) {

                if (array_key_exists($key . '_Alpha', $output)) {
                    $rgbdata = $this->HexToRGB($val);
                    $output[$key] = 'rgba(' . $rgbdata['R'] . ', ' . $rgbdata['G'] . ', ' . $rgbdata['B'] . ', ' . $output[$key . '_Alpha'] . ')';
                }else {
                    $rgbdata = $this->HexToRGB($val);
                    $output[$key] = 'rgb(' . $rgbdata['R'] . ', ' . $rgbdata['G'] . ', ' . $rgbdata['B'] . ')';
                }
            }
        }

        unset($output['Datasets']);
        unset($output['Libraries']);

        return $output;
    }

    private function LoadLibraries()
    {
        $html_str_js = '';
        $html_str_css = '';
        $start_js = true;
        $start_css = true;
        $Libraries = json_decode($this->ReadPropertyString('Libraries'), true);

        $arr_order = array_column($Libraries, 'Order');
        array_multisort($arr_order, SORT_ASC, $Libraries);

        foreach ($Libraries as $item) {
            if ($item['Type'] == 'Script') {
                //Javascript
                $str_js = '';
                if (!empty($item['Link'])) {
                    $str_js .= '<script src="' . $item['Link'] . '"></script>';
                }elseif ($item['Script'] > 0 && IPS_ScriptExists($item['Script'])) {
                    $str_js .= '<script src="{ADDRESS}/hook/JSLive/loadFile?Instance={INSTANCE}&pw={PASSWORD}&ident=' . $item['Ident'] . '"></script>';
                }elseif (!empty($item['File'])) {
                    $str_js .= '<script src="{ADDRESS}/hook/JSLive/loadFile?Instance={INSTANCE}&pw={PASSWORD}&ident=' . $item['Ident'] . '"></script>';
                }

                if (!empty($str_js)) {
                    if ($start_js) {
                        $start_js = false;
                        $html_str_js .= $str_js;
                    }else {
                        $html_str_js .= "\n\t" . $str_js;
                    }
                }
            }else {
                //CSS
                $str_css = '';
                if (!empty($item['Link'])) {
                    $str_css = '<link rel="stylesheet" href="' . $item['Link'] . '">';
                }elseif ($item['Script'] > 0 && IPS_ScriptExists($item['Script'])) {
                    $str_css = '<link rel="stylesheet" href="{ADDRESS}/hook/JSLive/loadFile?Instance={INSTANCE}&pw={PASSWORD}&ident=' . $item['Ident'] . '">';
                }elseif (!empty($item['File'])) {
                    $str_css = '<link rel="stylesheet" href="{ADDRESS}/hook/JSLive/loadFile?Instance={INSTANCE}&pw={PASSWORD}&ident=' . $item['Ident'] . '">';
                }

                if (!empty($str_css)) {
                    if ($start_css) {
                        $start_css = false;
                        $html_str_css .= $str_css;
                    }else {
                        $html_str_css .= "\n\t" . $str_css;
                    }
                }
            }
        }

        return ['js' => $html_str_js, 'css' => $html_str_css];
    }

}
