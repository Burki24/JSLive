<?php

declare(strict_types=1);

use Burki24\SymconModuleHelper\VariablePresentationNormalizationHelper;

/** Read-only producer of the shared de.burki24.jslive-echarts 1.0 package. */
final class JSLiveEChartsExport
{
    private array $assets = [];
    private array $issues = [];
    private string $chartKey;

    public function __construct(private int $instanceID, private Closure $readModel)
    {
        $this->chartKey = 'instance:' . $instanceID;
    }

    public function Export(string $installationID, bool $includeScripts = false): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $installationID) !== 1) {
            throw new RuntimeException('Migration installation identity is unavailable. Update the JSLive splitter first.');
        }
        $module = IPS_GetInstance($this->instanceID)['ModuleInfo'];
        $family = match ($module['ModuleName']) {
            'SymconJSLiveChart'       => 'cartesian-history',
            'SymconJSLiveDoughnutPie' => 'pie',
            'SymconJSLiveGauge'       => 'gauge',
            'SymconJSLiveRadarChart'  => 'radar-history',
            'SymconJSLiveProgressbar' => 'progress',
            default                   => throw new RuntimeException('This JSLive module has no ECharts chart export.')
        };
        for ($attempt = 0; $attempt < 3; ++$attempt) {
            $this->assets = [];
            $this->issues = [];
            $configuration = IPS_GetConfiguration($this->instanceID);
            $config = json_decode($configuration, true, 32, JSON_THROW_ON_ERROR);
            $state = $this->Runtime($family);
            $globals = $this->GlobalConfiguration();
            $chart = $this->Chart($config, $state, $module, $family, $includeScripts, $globals);
            if (IPS_GetConfiguration($this->instanceID) === $configuration && $this->Runtime($family) === $state && $this->GlobalConfiguration() === $globals) {
                $library = json_decode(file_get_contents(dirname(__DIR__, 2) . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
                $package = ['format' => 'de.burki24.jslive-echarts', 'formatVersion' => '1.0',
                    'exportId'       => self::UUID(), 'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
                    'producer'       => ['libraryId' => $library['id'], 'libraryVersion' => $library['version'],
                        'build'                      => $library['build'], 'exporterVersion' => '1.0.0',
                        'symconVersion'              => IPS_GetKernelVersion(), 'phpVersion' => PHP_VERSION],
                    'sourceInstallation' => ['id' => $installationID], 'charts' => [$chart],
                    'assets'             => array_values($this->assets), 'issues' => $this->issues, 'extensions' => new stdClass()];
                $previousPrecision = ini_get('serialize_precision');
                try {
                    ini_set('serialize_precision', '-1');
                    $json = json_encode($this->WireValue($package), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, 32);
                } finally {
                    ini_set('serialize_precision', $previousPrecision);
                }
                if (strlen($json) > 16777216) {
                    throw new RuntimeException('LIMIT_EXCEEDED: export exceeds 16 MiB.');
                }
                return $json;
            }
        }
        throw new RuntimeException('SOURCE_CHANGED: configuration or time controls changed during export.');
    }

    public static function UUID(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private function WireValue(mixed $value, string $key = ''): mixed
    {
        if (is_int($value) && ($value < -9007199254740991 || $value > 9007199254740991)) {
            throw new RuntimeException('PACKAGE_INVALID: integer exceeds the lossless JSON range.');
        }
        if (is_array($value)) {
            if ($value === [] && in_array($key, ['parameters', 'profiles', 'templates', 'extra', 'configuration', 'globalConfiguration', 'runtimeState'], true)) {
                return new stdClass();
            }
            foreach ($value as $name => $item) {
                $value[$name] = $this->WireValue($item, (string) $name);
            }
        }
        return $value;
    }

    private function Runtime(string $family): array
    {
        $idents = match ($family) {
            'cartesian-history' => ['Period', 'Now', 'Relativ', 'Offset', 'StartDate'],
            'radar-history'     => ['Period', 'Relativ'],
            default             => []
        };
        $result = [];
        foreach ($idents as $ident) {
            $id = IPS_GetObjectIDByIdent($ident, $this->instanceID);
            if (!IPS_VariableExists($id)) {
                throw new RuntimeException('SOURCE_UNRESOLVED: missing time control ' . $ident);
            }
            $value = GetValue($id);
            if ((in_array($ident, ['Now', 'Relativ'], true) && !is_bool($value))
                || (!in_array($ident, ['Now', 'Relativ'], true) && !is_int($value))) {
                throw new RuntimeException('PACKAGE_INVALID: time control has an invalid type.');
            }
            $result[$ident] = ['variableId' => $id, 'value' => $value];
        }
        return $result;
    }

    private function GlobalConfiguration(): array
    {
        $global = ($this->readModel)('GetGlobalConfiguration', []);
        if (!is_array($global)) {
            throw new RuntimeException('SOURCE_UNRESOLVED: parent rendering configuration is unavailable.');
        }
        // Never copy authentication, addresses, caches or the full parent config.
        return array_intersect_key($global, array_flip(['DataMode', 'RefreshTime', 'EnableViewport', 'viewport_content', 'CreateIPSView']));
    }

    private function Chart(array $config, array $state, array $module, string $family, bool $scripts, array $globals): array
    {
        $excluded = [];
        foreach (['LastUploadedConfig', 'Debug', 'EnableCache'] as $key) {
            if (array_key_exists($key, $config)) {
                $excluded[] = ['pointer' => '/' . $key, 'reason' => 'Operational state, not chart configuration.'];
                unset($config[$key]);
            }
        }
        if (strlen(json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) > 524288) {
            throw new RuntimeException('LIMIT_EXCEEDED: chart configuration exceeds 512 KiB.');
        }
        $sources = [];
        $bindings = [];
        $rings = [];
        $axes = isset($config['Axes']) ? $this->Rows($config['Axes']) : [];
        foreach ($axes as $index => &$axis) {
            $axis['presentation'] = $this->Profile($axis['Profile'] ?? '', '/Axes/' . $index);
        }
        unset($axis);
        if (isset($config['Variable'])) {
            $bindings[] = $this->Binding($config['Variable'], '/Variable', $config, 'value', $sources, true);
        }
        foreach ($this->Rows($config['Datasets'] ?? '[]') as $index => $row) {
            if ($family === 'pie') {
                $rings[] = ['settings' => $row, 'presentation' => $this->Profile($row['Profile'] ?? '', '/Datasets/' . $index . '/Profile')];
                foreach ($this->Rows($row['Variables'] ?? []) as $child => $item) {
                    $item['ring'] = $index;
                    $item['ringSettings'] = $row;
                    unset($item['ringSettings']['Variables']);
                    $item['Profile'] = $row['Profile'] ?? '';
                    $bindings[] = $this->Binding(
                        $item['Variable'] ?? 0,
                        '/Datasets/' . $index . '/Variables/' . $child,
                        $item,
                        'slice',
                        $sources,
                        true
                    );
                }
            } else {
                $binding = $this->Binding(
                    $row['Variable'] ?? 0,
                    '/Datasets/' . $index,
                    $row,
                    'value',
                    $sources,
                    $family !== 'cartesian-history' || ($row['Axes'] ?? '') !== ''
                );
                foreach ($axes as $axis) {
                    if (($axis['Ident'] ?? null) === ($row['Axes'] ?? null)) {
                        $binding['settings']['presentation'] = $axis['presentation'];
                    }
                }
                $bindings[] = $binding;
                if (is_int($row['Reference'] ?? null) && $row['Reference'] > 0) {
                    $bindings[] = $this->Binding($row['Reference'], '/Datasets/' . $index . '/Reference', $row, 'reference', $sources, true);
                }
            }
        }
        if (count($bindings) > 3200) {
            throw new RuntimeException('LIMIT_EXCEEDED: too many source bindings.');
        }
        $time = null;
        if ($state !== []) {
            $window = ($this->readModel)('GetCorrectStartDate', []);
            $queries = [];
            foreach ($bindings as $binding) {
                $settings = $binding['settings'];
                $offset = $settings['Offset'] ?? 0;
                $range = $offset > 0 ? ($this->readModel)('GetOffsetDate', [$window['start'], $window['end'], $offset]) : ['start' => $window['start'], 'end' => $window['end'], 'interval' => 0];
                $queries[] = ['bindingKey' => $binding['bindingKey'], 'sourceKey' => $binding['sourceKey'],
                    'fromInclusive'        => $range['start'], 'untilExclusive' => $range['end'] + 1,
                    'displayOffsetSeconds' => $range['interval'], 'aggregationLevel' => $window['stufe'],
                    'processing'           => $this->Processing($family, $state, $settings, $config, $sources[$binding['sourceKey']]['archive'] ?? []),
                    'rulesVersion'         => 'jslive-' . $family . '-1', 'originalRules' => [
                        'period'  => $state['Period']['value'], 'relative' => $state['Relativ']['value'],
                        'highRes' => $settings['HighRes'] ?? null, 'highResSteps' => $config['data_highResSteps'] ?? null,
                        'mode'    => $settings['Mode'] ?? null, 'precision' => $config['data_precision'] ?? null]];
            }
            $time = ['timezone'  => date_default_timezone_get(), 'selection' => $state,
                'resolvedWindow' => ['fromInclusive' => $window['start'], 'untilExclusive' => $window['end'] + 1],
                'rulesVersion'   => 'jslive-' . $family . '-1', 'queries' => $queries];
        }
        $appearance = ['settings' => $config, 'global' => $globals, 'rings' => $rings, 'contexts' => ['tile', 'ipsview'], 'resolved' => []];
        foreach (['GetConfigurationData', 'GenerateTitleData', 'GenerateLegendData', 'GenerateTooltipData', 'GenerateYAxesData', 'GenerateTicks', 'GenerateHighlights', 'GenerateLabels'] as $method) {
            try {
                $appearance['resolved'][$method] = ($this->readModel)($method, []);
            } catch (ReflectionException) {
                // Some families do not have this model component.
            } catch (Throwable $exception) {
                $this->Issue('DESIGN_UNRESOLVED', '/appearance/' . $method, $exception->getMessage());
            }
        }
        foreach ($config as $key => $value) {
            if (str_ends_with($key, '_fontFamily') && is_string($value) && $value !== '') {
                $this->Font($value);
            }
        }
        $standardTemplate = match ($family) {
            'cartesian-history' => 'Chart', 'pie' => 'Doughnut-PIE', 'radar-history' => 'RadarChart',
            'progress'          => 'Progressbar', 'gauge' => $config['template'] ?? 'CanvasGauges-Radial'
        };
        $allowedTemplates = ['Chart', 'Doughnut-PIE', 'RadarChart', 'Progressbar', 'CanvasGauges-Radial',
            'CanvasGauges-Linear', 'CanvasGauges-Linear(vertical)', 'CanvasGauges-Compass'];
        if (in_array($standardTemplate, $allowedTemplates, true)) {
            $templatePath = dirname(__DIR__) . '/templates/' . $standardTemplate . '.html';
            if (is_file($templatePath)) {
                $this->Asset(
                    file_get_contents($templatePath),
                    'text/plain',
                    'inert-standard-template',
                    ['template' => $standardTemplate, 'license' => 'GPL-3.0', 'resource' => 'templates/' . $standardTemplate . '.html']
                );
            } else {
                $this->Issue('ASSET_UNAVAILABLE', '/template', 'Standard template file is unavailable.');
            }
        } else {
            $this->Issue('ASSET_UNAVAILABLE', '/template', 'Unknown standard template; no arbitrary file was read.');
        }
        foreach (['shape_svg' => 'image/svg+xml', 'shape_path' => 'text/plain', 'fill_backgroundFile' => $config['fill_backgroundFileType'] ?? 'application/octet-stream'] as $key => $mime) {
            if (is_string($config[$key] ?? null) && $config[$key] !== '') {
                $bytes = in_array($key, ['shape_svg', 'fill_backgroundFile'], true) ? base64_decode($config[$key], true) : $config[$key];
                if ($bytes === false || ($key === 'shape_svg' && str_contains($config[$key], '<svg'))) {
                    $bytes = $config[$key];
                }
                $this->Asset($bytes, $mime, 'shape', ['property' => $key]);
            }
        }
        $scriptID = $config['TemplateScriptID'] ?? 0;
        if ($scriptID > 0) {
            if ($scripts && IPS_ScriptExists($scriptID)) {
                $this->Asset(IPS_GetScriptContent($scriptID), 'text/plain', 'inert-template', ['scriptId' => $scriptID, 'name' => IPS_GetObject($scriptID)['ObjectName']]);
            }
            $this->Issue('CUSTOM_CODE_INERT', '/TemplateScriptID', $scripts ? 'Custom template retained as inert text; no automatic rendering equivalence.' : 'Custom template content deliberately excluded.');
        }
        $coverage = [];
        foreach ($config as $key => $_) {
            $pointer = '/' . str_replace(['~', '/'], ['~0', '~1'], $key);
            $coverage[] = ['pointer' => $pointer, 'status' => 'exact', 'target' => '/original/configuration' . $pointer,
                'rule'               => 'export-original-1', 'reason' => 'Complete original field preserved; target mapping is separately validated.'];
        }
        return ['chartKey' => $this->chartKey, 'origin' => ['instanceId' => $this->instanceID,
            'moduleId'                                                   => $module['ModuleID'], 'moduleName' => $module['ModuleName'],
            'instanceName'                                               => IPS_GetObject($this->instanceID)['ObjectName'], 'family' => $family,
            'templateVariant'                                            => $config['template'] ?? $config['type'] ?? $config['shape_preset'] ?? 'standard'],
            'original'   => ['configuration' => $config, 'globalConfiguration' => $globals, 'runtimeState' => $state, 'excluded' => $excluded],
            'sources'    => array_values($sources), 'bindings' => $bindings, 'axes' => $axes, 'time' => $time,
            'appearance' => $appearance, 'assetRefs' => array_keys($this->assets), 'coverage' => $coverage, 'reference' => null];
    }

    private function Binding(mixed $id, string $pointer, array $settings, string $role, array &$sources, bool $enabled): array
    {
        if (!is_int($id) || $id <= 0 || !IPS_VariableExists($id)) {
            $this->Issue('SOURCE_UNRESOLVED', $pointer, 'Source variable is unavailable.');
            $key = null;
        } else {
            $key = 'variable:' . $id;
            if (!isset($sources[$key])) {
                $variable = IPS_GetVariable($id);
                try {
                    $presentation = VariablePresentationNormalizationHelper::Capture($id);
                } catch (Throwable $exception) {
                    $presentation = ['original' => $variable, 'effective' => null, 'native' => null, 'conversion' => ['status' => 'blocked', 'issues' => [['message' => $exception->getMessage()]]]];
                }
                if (($presentation['conversion']['status'] ?? '') !== 'exact') {
                    $this->Issue('PRESENTATION_UNSUPPORTED', $pointer, 'Source presentation could not be fully translated; original metadata retained.');
                }
                $sources[$key] = ['sourceKey' => $key, 'variableId' => $id, 'variableType' => $variable['VariableType'],
                    'label'                   => IPS_GetObject($id)['ObjectName'], 'presentation' => $presentation, 'archive' => $this->Archive($id)];
            }
        }
        if (is_string($settings['Profile'] ?? null) && $settings['Profile'] !== '') {
            $settings['presentation'] = $this->Profile($settings['Profile'], $pointer . '/Profile');
        }
        return ['bindingKey' => $this->chartKey . '#' . $pointer, 'sourceKey' => $key, 'role' => $role,
            'enabled'        => $enabled, 'settings' => $settings, 'appearance' => $settings, 'originalPointer' => $pointer];
    }

    private function Processing(string $family, array $state, array $settings, array $config, array $archive): array
    {
        $period = $state['Period']['value'];
        $raw = $family === 'cartesian-history' ? ($settings['HighRes'] ?? 7) <= $period : $period >= 6;
        $counter = ($archive['aggregationType'] ?? null) === 1;
        return ['mode'         => $raw ? 'raw' : 'aggregated', 'valueField' => $raw ? 'Value' : 'Avg',
            'counter'          => $counter, 'rawCounterRule' => $counter && $raw ? 'absolute-adjacent-difference-current-value-boundary-v1' : null,
            'precision'        => $counter && $family === 'cartesian-history' ? 5 : ($config['data_precision'] ?? 2),
            'decimation'       => $raw && $family === 'cartesian-history' && $period !== 7 ? ($config['data_highResSteps'] ?? 1) : 1,
            'missingData'      => $family === 'cartesian-history' ? 'legacy-current-value-fallback-unless-boolean' : 'legacy-zero-initialized-buckets',
            'runningIntervals' => 'jslive-includes-current-interval-with-browser-updates',
            'radarMode'        => $family === 'radar-history' ? ($settings['Mode'] ?? null) : null,
            'radarReference'   => $family === 'radar-history' ? ($settings['Reference'] ?? 0) : null,
            'radarReducer'     => $family === 'radar-history' ? 'legacy-reference-count-or-last-assigned-bucket-value-v1' : null,
            'boundary'         => 'inclusive-kernel-range-adapted-to-exclusive-plan', 'timestampUnit' => 'seconds'];
    }

    private function Profile(string $name, string $pointer): ?array
    {
        if ($name === '') {
            return null;
        }
        if (!IPS_VariableProfileExists($name)) {
            $this->Issue('PROFILE_UNRESOLVED', $pointer, 'Selected chart profile is unavailable.');
            return ['original' => ['profileName' => $name], 'native' => null, 'conversion' => ['status' => 'blocked']];
        }
        $profile = IPS_GetVariableProfile($name);
        // JSLive axes/rings use the profile suffix as text, not native percent scaling.
        $result = VariablePresentationNormalizationHelper::Normalize($profile['ProfileType'], ['PROFILE' => $name, 'PERCENTAGE' => false], [$name => $profile]);
        if ($result['conversion']['status'] !== 'exact') {
            $this->Issue('PRESENTATION_UNSUPPORTED', $pointer, 'Selected chart profile requires an explicit conversion rule.');
        }
        return $result;
    }

    private function Archive(int $id): array
    {
        $ids = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
        if ($ids === []) {
            return ['archiveId' => null, 'logging' => false, 'aggregationType' => null, 'counterSettings' => null, 'availability' => 'missing'];
        }
        $archive = $ids[0];
        try {
            $type = AC_GetAggregationType($archive, $id);
            return ['archiveId'   => $archive, 'logging' => AC_GetLoggingStatus($archive, $id),
                'aggregationType' => $type, 'counterSettings' => $type === 1 ? ['ignoreZeros' => AC_GetCounterIgnoreZeros($archive, $id)] : null,
                'availability'    => 'metadata-only'];
        } catch (Throwable $exception) {
            $this->Issue('ARCHIVE_UNAVAILABLE', '/sources/' . $id . '/archive', 'Archive metadata could not be read.');
            return ['archiveId' => $archive, 'logging' => null, 'aggregationType' => null, 'counterSettings' => null, 'availability' => 'unresolved'];
        }
    }

    private function Font(string $name): void
    {
        if (preg_match('/^[A-Za-z0-9 _-]+$/D', $name) !== 1) {
            $this->Issue('ASSET_UNAVAILABLE', '/fonts', 'Unsafe or unknown font name.');
            return;
        }
        $root = realpath(dirname(__DIR__) . '/js');
        $path = realpath($root . '/css/fonts/' . $name . '.css');
        if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            $this->Issue('ASSET_UNAVAILABLE', '/fonts', 'Font stylesheet is unavailable.');
            return;
        }
        $css = file_get_contents($path);
        $this->Asset($css, 'text/css', 'inert-font-css', ['font' => $name, 'resource' => '/hook/JSLive/js/css/fonts/' . $name . '.css']);
        preg_match_all('/url\(\s*[\'"]?([^\)\'"\s]+)[\'"]?\s*\)/i', $css, $matches);
        foreach ($matches[1] as $relative) {
            $resource = str_starts_with($relative, '/hook/JSLive/js/')
                ? $root . '/' . substr($relative, strlen('/hook/JSLive/js/'))
                : dirname($path) . '/' . $relative;
            $file = realpath($resource);
            if ($file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
                $this->Issue('ASSET_UNAVAILABLE', '/fonts', 'Font resource is external or unavailable; no network request performed.');
                continue;
            }
            $this->Asset(file_get_contents($file), 'font/' . pathinfo($file, PATHINFO_EXTENSION), 'font', ['font' => $name, 'resource' => $relative]);
        }
    }

    private function Asset(string $bytes, string $mime, string $kind, array $origin): void
    {
        $hash = hash('sha256', $bytes);
        $key = 'sha256:' . $hash;
        if (isset($this->assets[$key])) {
            return;
        }
        if (strlen($bytes) > 1048576 || count($this->assets) >= 512
            || array_sum(array_column($this->assets, 'byteLength')) + strlen($bytes) > 8388608) {
            throw new RuntimeException('LIMIT_EXCEEDED: export resources exceed the package limits.');
        }
        $this->assets[$key] = ['assetId' => $key, 'kind' => $kind, 'mediaType' => $mime, 'byteLength' => strlen($bytes),
            'sha256'                     => $hash, 'contentBase64' => base64_encode($bytes), 'origin' => $origin + ['license' => 'not-inferred']];
    }

    private function Rows(mixed $rows): array
    {
        if (is_string($rows)) {
            $rows = json_decode($rows, true, 32, JSON_THROW_ON_ERROR);
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('PACKAGE_INVALID: chart list is invalid.');
        }
        return $rows;
    }

    private function Issue(string $code, string $pointer, string $message): void
    {
        $this->issues[] = ['code' => $code, 'severity' => 'error', 'chartKey' => $this->chartKey, 'pointer' => $pointer,
            'message'             => $message, 'effect' => 'Full automatic import is blocked until resolved.', 'resolution' => 'Review the retained original and resolve before import.'];
    }
}
