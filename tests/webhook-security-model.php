<?php

declare(strict_types=1);

if (!class_exists('IPSModuleStrict')) {
    class IPSModuleStrict
    {
    }
}

$moduleClasses = [
    'SymconJSLiveAdvTextfield'  => 'SymconJSLiveAdvTextfield/module.php',
    'SymconJSLiveChart'         => 'SymconJSLiveChart/module.php',
    'SymconJSLiveColorPicker'   => 'SymconJSLiveColorPicker/module.php',
    'SymconJSLiveCustom'        => 'SymconJSLiveCustom/module.php',
    'SymconJSLiveDateTimePicker'=> 'SymconJSLiveDateTimePicker/module.php',
    'SymconJSLiveDoughnutPie'   => 'SymconJSLiveDoughnutPie/module.php',
    'SymconJSLiveGauge'         => 'SymconJSLiveGauge/module.php',
    'SymconJSLiveProgressbar'   => 'SymconJSLiveProgressbar/module.php',
    'SymconJSLiveRadarChart'    => 'SymconJSLiveRadarChart/module.php'
];

$expectedCommands = [
    'SymconJSLiveAdvTextfield'   => ['exportConfiguration', 'getContend', 'getData', 'setData'],
    'SymconJSLiveChart'          => ['getConfiguration', 'getLanguage', 'getFonts', 'exportConfiguration', 'getContend', 'getUpdate', 'getData'],
    'SymconJSLiveColorPicker'    => ['exportConfiguration', 'getContend', 'getData', 'setData'],
    'SymconJSLiveCustom'         => ['exportConfiguration', 'getContend', 'getData', 'setData', 'loadFile'],
    'SymconJSLiveDateTimePicker' => ['exportConfiguration', 'getContend', 'getData', 'setData'],
    'SymconJSLiveDoughnutPie'    => ['exportConfiguration', 'getContend', 'getUpdate', 'getData'],
    'SymconJSLiveGauge'          => ['exportConfiguration', 'getContend', 'getData'],
    'SymconJSLiveProgressbar'    => ['exportConfiguration', 'getContend', 'getData', 'getSVG', 'getFillImg'],
    'SymconJSLiveRadarChart'     => ['exportConfiguration', 'getContend', 'getUpdate', 'getData']
];

$root = dirname(__DIR__);
foreach ($moduleClasses as $class => $relativePath) {
    require_once $root . '/' . $relativePath;

    $method = new ReflectionMethod($class, 'ReceiveData');
    $sourceLines = file($method->getFileName());
    if ($sourceLines === false) {
        throw new RuntimeException('Cannot read ReceiveData source for ' . $class . '.');
    }

    $methodSource = implode('', array_slice(
        $sourceLines,
        $method->getStartLine() - 1,
        $method->getEndLine() - $method->getStartLine() + 1
    ));
    preg_match_all("/case '([^']+)':/", $methodSource, $matches);
    $actualCommands = $matches[1] ?? [];

    if ($actualCommands !== $expectedCommands[$class]) {
        throw new RuntimeException(
            $class . ' changed its characterized webhook command list: '
                . json_encode($actualCommands, JSON_THROW_ON_ERROR)
        );
    }
}

$writeModules = array_keys(array_filter(
    $expectedCommands,
    static fn (array $commands): bool => in_array('setData', $commands, true)
));
if ($writeModules !== [
    'SymconJSLiveAdvTextfield',
    'SymconJSLiveColorPicker',
    'SymconJSLiveCustom',
    'SymconJSLiveDateTimePicker'
]) {
    throw new RuntimeException('The characterized setData module inventory changed.');
}

fwrite(STDOUT, "JSLive webhook command and write model verified.\n");
