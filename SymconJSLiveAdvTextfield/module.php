<?php

declare(strict_types=1);
include_once __DIR__ . '/../SymconJSLive/libs/JSLiveModule.php';

class SymconJSLiveAdvTextfield extends JSLiveModule
{
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Template', 'Textfield1');

        //Expert
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyInteger('ViewLevel', 0);
        $this->RegisterPropertyBoolean('EnableCache', true);
        $this->RegisterPropertyBoolean('CreateOutput', true);
        $this->RegisterPropertyBoolean('CreateIPSView', true);
        $this->RegisterPropertyInteger('TemplateScriptID', 0);
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
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterVariableString('Content', $this->Translate('Content'), [], 0);

    }

    public function ReceiveData(string $JSONString): string
    {
        parent::ReceiveData($JSONString);
        $jsonData = json_decode($JSONString, true);
        $buffer = json_decode($jsonData['Buffer'], true);

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
        $output['Variable'] = IPS_GetObjectIDByIdent('Content', $this->InstanceID);
        $output['Value'] = $this->GetValue('Content');
        return json_encode($output, JSON_THROW_ON_ERROR);
    }
    private function SetData(array $querydata)
    {
        if (!array_key_exists('val', $querydata)) {
            $this->SendSafeDebug('SetData', 'NO VALUE SET!');
            return 'NO VALUE SET!';
        }

        $val = $querydata['val'];
        $this->SendSafeDebug('SetData', 'Update Content');
        $this->SetValue('Content', $val);
        return 'OK';
    }

    private function ReplacePlaceholder(string $htmlData)
    {
        //configuration Data
        $htmlData = str_replace('{CONFIG}', $this->json_encode_advanced($this->GetConfigurationData()), $htmlData);

        //Value
        $htmlData = str_replace('{VALUE}', "'" . addslashes($this->GetValue('Content')) . "'", $htmlData);

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

        $output['Variable'] = IPS_GetObjectIDByIdent('Content', $this->InstanceID);

        return $output;
    }
}
