<?php

declare(strict_types=1);
include_once __DIR__ . '/../SymconJSLive/libs/JSLiveModule.php';

class SymconJSLiveColorPicker extends JSLiveModule
{
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        //Expert
        $this->RegisterPropertyBoolean('Debug', false);
        $this->RegisterPropertyInteger('ViewLevel', 0);
        $this->RegisterPropertyBoolean('EnableCache', true);
        $this->RegisterPropertyBoolean('CreateOutput', true);
        $this->RegisterPropertyBoolean('CreateIPSView', true);
        $this->RegisterPropertyInteger('TemplateScriptID', 0);
        $this->RegisterPropertyInteger('DataUpdateRate', 250);
        $this->RegisterPropertyInteger('manWidth', 0);
        $this->RegisterPropertyBoolean('EnableViewport', true);
        $this->RegisterPropertyInteger('IFrameHeight', 0);
        $this->RegisterPropertyInteger('overrideWidth', 0);
        $this->RegisterPropertyInteger('overrideHeight', 0);

        //style
        $this->RegisterPropertyInteger('style_borderWidth', 2);
        $this->RegisterPropertyInteger('style_borderColor', 0);
        $this->RegisterPropertyInteger('style_handleRadius', 8);

        //wheel
        $this->RegisterPropertyBoolean('wheel_Lightness', true);
        $this->RegisterPropertyInteger('wheel_Angle', 0);
        $this->RegisterPropertyString('wheel_Direction', 'anticlockwise');

        //layout
        $this->RegisterPropertyString('layout_Direction', 'horizontal');
        $this->RegisterPropertyString('Layout', '[]');

        //variables
        $this->RegisterPropertyString('Datasets', '[]');

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
                return $this->ExportConfiguration(false, $buffer['queryData'] ?? []);
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

            //bestimmte aktuelle einstellungen beibehalten
            $confData['Variables'] = $this->ReadPropertyString('Datasets');

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
            $scriptData = file_get_contents(__DIR__ . '/../SymconJSLive/templates/ColorPicker.html');
        }else {
            if (!IPS_ScriptExists($scriptID)) {
                $this->SendSafeDebug('GetWebpage', 'Template NOT FOUND!');
                return 'Template NOT FOUND!';
            }

            $scriptData = IPS_GetScriptContent($scriptID);
            if ($scriptData == '') {
                return 'Template IS EMPTY!';
            }
        }

        $scriptData = $this->ReplacePlaceholder($scriptData);

        if ($this->ReadPropertyBoolean('Debug'))
            $this->SendSafeDebug('GetWebpage', ['renderedBytes' => strlen($scriptData)]);

        return $scriptData;
    }
    private function GetData(array $querydata)
    {
        return json_encode($this->GenerateVariabels(), JSON_THROW_ON_ERROR);
    }
    private function SetData(array $querydata)
    {
        if (!array_key_exists('var', $querydata) || !array_key_exists('val', $querydata)) {
            $this->SendSafeDebug('SetData', 'NO VARIABLE, OR VALUE SET!');
            return 'NO VARIABLE, OR VALUE SET!';
        }

        $var_data = json_decode($this->ReadPropertyString('Datasets'), true);
        $var_ids = [];
        $xyVariable = false;
        foreach ($var_data as $varItem) {
            if ($varItem['Variable'] > 0)
                $var_ids[] = $varItem['Variable'];
            if ($varItem['Variable'] == $querydata['var'] && ($varItem['Mode'] ?? '') === 'xy') {
                $xyVariable = true;
            }
        }

        if (!in_array($querydata['var'], $var_ids)) {
            $this->SendSafeDebug('SetData', 'VARIABLE NOT IN LIST SET!');
            return 'VARIABLE NOT IN LIST SET!';
        }

        $this->SendSafeDebug('SetData', 'Update Variable');
        if ($xyVariable) {
            $value = $querydata['val'];
            if ((!is_int($value) && (!is_string($value) || preg_match('/^[0-9]{1,8}$/D', $value) !== 1))
                || (int) $value < 0 || (int) $value > 0xFFFFFF) {
                return 'INVALID RGB COLOR!';
            }
            try {
                $this->RequireXYColor((int) $querydata['var']);
                return RequestActionColor((int) $querydata['var'], (int) $value) ? 'OK' : 'XY COLOR UPDATE FAILED!';
            } catch (Throwable $exception) {
                $this->SendSafeDebug('SetData', 'XY COLOR UPDATE FAILED!');
                return 'XY COLOR UPDATE FAILED!';
            }
        }
        RequestAction($querydata['var'], $querydata['val']);
        return 'OK';
    }

    private function RequireXYColor(int $variableID): void
    {
        if (!function_exists('GetValueColor') || !function_exists('RequestActionColor')) {
            throw new RuntimeException('Native color functions are unavailable. Update Symcon.');
        }
        if (!IPS_VariableExists($variableID) || IPS_GetVariable($variableID)['VariableType'] !== 3) {
            throw new RuntimeException('An xy color requires a string variable.');
        }
        $presentation = IPS_GetVariablePresentation($variableID);
        if (($presentation['PRESENTATION'] ?? '') !== '{05CC3CC2-A0B2-5837-A4A7-A07EA0B9DDFB}'
            || ($presentation['ENCODING'] ?? null) !== 4) {
            throw new RuntimeException('An xy color requires the Color presentation with xy encoding.');
        }
    }

    private function ReplacePlaceholder(string $htmlData)
    {
        // xy values contain JSON strings; preserve quotes/null and keep script text inert.
        $jsonFlags = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        //configuration Data
        $htmlData = str_replace('{CONFIG}', json_encode($this->GetConfigurationData(), $jsonFlags), $htmlData);

        //variables
        $htmlData = str_replace('{VARIABELS}', json_encode($this->GenerateVariabels(), $jsonFlags), $htmlData);

        //Layout
        $htmlData = str_replace('{LAYOUT}', json_encode($this->GenerateLayout(), $jsonFlags), $htmlData);

        return $htmlData;
    }
    private function GenerateVariabels()
    {
        $output = [];
        $variables = json_decode($this->ReadPropertyString('Datasets'), true);

        //load all variables
        foreach ($variables as $item) {
            if (IPS_VariableExists($item['Variable'])) {
                $s_output = [];

                $s_output['Variable'] = $item['Variable'];
                $s_output['Value'] = GetValue($item['Variable']);
                $s_output['Mode'] = $item['Mode'];
                if ($item['Mode'] === 'xy') {
                    // Preserve the raw value contract; only the picker uses the RGB projection.
                    $s_output['ColorValue'] = null;
                    $s_output['Error'] = '';
                    try {
                        $this->RequireXYColor($item['Variable']);
                        $color = GetValueColor($item['Variable']);
                        if (!is_int($color) || $color < 0 || $color > 0xFFFFFF) {
                            throw new RuntimeException('Invalid native color value.');
                        }
                        $s_output['ColorValue'] = $color;
                    } catch (Throwable $exception) {
                        $s_output['Error'] = $this->Translate('Cannot read xy color. Check the variable, Color presentation, xy encoding and Symcon update.');
                    }
                }

                $output[] = $s_output;
            }
        }

        return $output;
    }
    private function GenerateLayout()
    {
        $layout_data = json_decode($this->ReadPropertyString('Layout'), true);

        $arr_order = array_column($layout_data, 'Order');
        array_multisort($arr_order, SORT_ASC, $layout_data);

        return $layout_data;
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

        unset($output['Layout']);
        unset($output['Datasets']);
        $output['ColorUpdateError'] = $this->Translate('Color update failed');
        return $output;
    }
}
