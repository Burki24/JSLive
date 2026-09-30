<?php

declare(strict_types=1);
include_once __DIR__ . '/../SymconJSLive/libs/JSLiveModule.php';

class SymconJSLiveDateTimePicker extends JSLiveModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}');

        $this->RegisterPropertyInteger('Variable', 0);
        $this->RegisterPropertyString('Template', 'TimePicker1');

        //Expert
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyInteger('ViewLevel', 0);
        $this->RegisterPropertyInteger('TemplateScriptID', 0);
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
    }
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

    }

    public function ReceiveData($JSONString)
    {
        parent::ReceiveData($JSONString);
        $jsonData = json_decode($JSONString, true);
        $buffer = json_decode($jsonData['Buffer'], true);

        //if($buffer["instance"] != $this->InstanceID) return;
        switch ($buffer['cmd']) {
            case 'exportConfiguration':
                return $this->ExportConfiguration();
            case 'getContend':
                return $this->GetOutput();
            case 'getData':
                return $this->GetData($buffer['queryData']);
            case 'setData':
                return $this->SetData($buffer['queryData']);
            default:
                if ($buffer['cmd'] != 'UpdateCache')
                    $this->SendSafeDebug('ReceiveData', 'ACTION FOR THIS MODULE NOT DEFINED!');
                break;
        }
    }

    public function LoadOtherConfiguration(int $id)
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

            //bestimmte aktuelle einstellungen beibehalten
            $confData['Variables'] = $this->ReadPropertyString('Variables');

            IPS_SetConfiguration($this->InstanceID, json_encode($confData));
            IPS_ApplyChanges($this->InstanceID);
        }else return 'A Instance must be selected!';
    }
    protected function GetWebpage()
    {
        $scriptID = $this->ReadPropertyInteger('TemplateScriptID');
        if (empty($scriptID)) {
            if ($this->ReadPropertyBoolean('Debug'))
                $this->SendSafeDebug('GetWebpage', 'load default template!');
            $scriptData = file_get_contents(__DIR__ . '/../SymconJSLive/templates/' . $this->ReadPropertyString('Template') . '.html');
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
    private function GetData(array $querydata)
    {
        $output = [];
        $output['Variable'] = $this->ReadPropertyInteger('Variable');
        if (IPS_VariableExists($this->ReadPropertyInteger('Variable'))) {
            $output['Value'] = GetValue($this->ReadPropertyInteger('Variable'));
        }else {
            $this->SendSafeDebug('SetData', 'VARIABLE NOT EXIST!');
            $output['Value'] = 0;
        }
        return json_encode($output);
    }
    private function SetData(array $querydata)
    {
        if (!array_key_exists('var', $querydata) || !array_key_exists('val', $querydata)) {
            $this->SendSafeDebug('SetData', 'NO VARIABLE, OR VALUE SET!');
            return 'NO VARIABLE, OR VALUE SET!';
        }

        $variableID = $this->ReadPropertyInteger('Variable');
        if ($querydata['var'] != $variableID) {
            $this->SendSafeDebug('SetData', 'VARIABLE NOT SET!');
            return 'VARIABLE NOT SET!';
        }

        if (!IPS_VariableExists($variableID)) {
            $this->SendSafeDebug('SetData', 'VARIABLE NOT EXIST!');
            return 'VARIABLE NOT EXIST!';
        }

        $curTimezone = new DateTime();
        $timeVal = new DateTime();
        $timeVal->setTimestamp((int) $querydata['val']);
        $timeVal->sub(new DateInterval('PT' . $timeVal->getOffset() . 'S'));

        $this->SendSafeDebug('SetData', 'Update Variable');

        if (IPS_GetVariable($variableID)['VariableAction'] > 0) {
            RequestAction($variableID, $timeVal->getTimestamp());
        }else {
            SetValue($variableID, $timeVal->getTimestamp());
        }

        return 'OK';
    }

    private function ReplacePlaceholder(string $htmlData)
    {
        //configuration Data
        $htmlData = str_replace('{CONFIG}', $this->json_encode_advanced($this->GetConfigurationData()), $htmlData);

        //variables
        if (IPS_VariableExists($this->ReadPropertyInteger('Variable'))) {
            $htmlData = str_replace('{VALUE}', (string) GetValue($this->ReadPropertyInteger('Variable')), $htmlData);
        }else {
            $this->SendSafeDebug('SetData', 'VARIABLE NOT EXIST!');
            $htmlData = str_replace('{VALUE}', '0', $htmlData);
        }

        //Load Fonts
        $htmlData = str_replace('{FONTS}', $this->LoadFonts(), $htmlData);

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

        return $output;
    }
}
