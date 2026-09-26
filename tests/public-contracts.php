<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$fixturePath = __DIR__ . '/fixtures/public-contracts.json';

/**
 * @throws RuntimeException When the condition is not met.
 */
function assertPublicContract(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @return array<string, mixed>
 */
function readContractJson(string $path): array
{
    $contents = file_get_contents($path);
    assertPublicContract($contents !== false, 'Unable to read contract file: ' . $path);

    try {
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException($path . ': ' . $exception->getMessage(), 0, $exception);
    }

    assertPublicContract(is_array($data), $path . ' must contain a JSON object.');

    return $data;
}

/**
 * Returns the literal arguments of a function call token.
 *
 * @param array<int, array{int, string, int}|string> $tokens
 *
 * @return list<string>
 */
function getCallArguments(array $tokens, int $functionIndex): array
{
    $tokenCount = count($tokens);
    $openIndex = $functionIndex + 1;
    while ($openIndex < $tokenCount) {
        $token = $tokens[$openIndex];
        if ($token === '(') {
            break;
        }
        if (is_array($token) && !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            return [];
        }
        ++$openIndex;
    }

    if ($openIndex >= $tokenCount || $tokens[$openIndex] !== '(') {
        return [];
    }

    $arguments = [];
    $current = '';
    $depth = 1;
    for ($index = $openIndex + 1; $index < $tokenCount; ++$index) {
        $token = $tokens[$index];
        $text = is_array($token) ? $token[1] : $token;

        if ($text === '(' || $text === '[' || $text === '{') {
            ++$depth;
        } elseif ($text === ')' || $text === ']' || $text === '}') {
            --$depth;
            if ($depth === 0) {
                $arguments[] = trim($current);

                return $arguments === [''] ? [] : $arguments;
            }
        }

        if ($text === ',' && $depth === 1) {
            $arguments[] = trim($current);
            $current = '';
            continue;
        }

        $current .= $text;
    }

    return [];
}

function decodeContractString(string $expression): ?string
{
    if (strlen($expression) < 2) {
        return null;
    }

    $quote = $expression[0];
    if (($quote !== "'" && $quote !== '"') || $expression[strlen($expression) - 1] !== $quote) {
        return null;
    }

    $value = substr($expression, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $value);
    }

    return stripcslashes($value);
}

/**
 * Extracts stable, externally relevant declarations without loading a Symcon runtime.
 *
 * @return array{properties: list<string>, variables: list<string>, actions: list<string>, publicMethods: list<string>}
 */
function extractSourceContracts(string $path): array
{
    $source = file_get_contents($path);
    assertPublicContract($source !== false, 'Unable to read source contract: ' . $path);
    $tokens = token_get_all($source);

    $properties = [];
    $variables = [];
    $actions = [];
    $publicMethods = [];
    $tokenCount = count($tokens);

    for ($index = 0; $index < $tokenCount; ++$index) {
        $token = $tokens[$index];
        if (!is_array($token)) {
            continue;
        }

        if ($token[0] === T_PUBLIC) {
            for ($lookahead = $index + 1; $lookahead < $tokenCount; ++$lookahead) {
                $next = $tokens[$lookahead];
                if (
                    is_array($next)
                    && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_STATIC, T_FINAL, T_ABSTRACT], true)
                ) {
                    continue;
                }
                if (!is_array($next) || $next[0] !== T_FUNCTION) {
                    break;
                }

                for (++$lookahead; $lookahead < $tokenCount; ++$lookahead) {
                    $nameToken = $tokens[$lookahead];
                    if (is_array($nameToken) && $nameToken[0] === T_STRING) {
                        $publicMethods[] = $nameToken[1];
                        break;
                    }
                }
                break;
            }
        }

        if ($token[0] !== T_STRING) {
            continue;
        }

        if (preg_match('/^RegisterProperty(Boolean|Integer|Float|String)$/', $token[1], $matches) === 1) {
            $arguments = getCallArguments($tokens, $index);
            $name = isset($arguments[0]) ? decodeContractString($arguments[0]) : null;
            assertPublicContract($name !== null, $path . ': property registration without a literal name.');
            $properties[] = $name . ':' . strtolower($matches[1]);
            continue;
        }

        if (preg_match('/^RegisterVariable(Boolean|Integer|Float|String)$/', $token[1], $matches) === 1) {
            $arguments = getCallArguments($tokens, $index);
            $name = isset($arguments[0]) ? decodeContractString($arguments[0]) : null;
            $profile = isset($arguments[2]) ? decodeContractString($arguments[2]) : null;
            assertPublicContract($name !== null, $path . ': variable registration without a literal ident.');
            assertPublicContract($profile !== null, $path . ': variable registration without a literal profile.');
            $variables[] = $name . ':' . strtolower($matches[1]) . ':' . $profile;
            continue;
        }

        if ($token[1] === 'EnableAction') {
            $arguments = getCallArguments($tokens, $index);
            $name = isset($arguments[0]) ? decodeContractString($arguments[0]) : null;
            assertPublicContract($name !== null, $path . ': action registration without a literal ident.');
            $actions[] = $name;
        }
    }

    $contracts = [
        'properties'    => array_values(array_unique($properties)),
        'variables'     => array_values(array_unique($variables)),
        'actions'       => array_values(array_unique($actions)),
        'publicMethods' => array_values(array_unique($publicMethods))
    ];
    foreach ($contracts as &$items) {
        sort($items, SORT_STRING);
    }
    unset($items);

    return $contracts;
}

$fixture = readContractJson($fixturePath);
assertPublicContract(($fixture['schema'] ?? null) === 1, 'The public contract fixture must use schema 1.');

$expectedLibrary = $fixture['library'] ?? null;
assertPublicContract(is_array($expectedLibrary), 'The public contract fixture is missing the library contract.');
$library = readContractJson($root . '/library.json');
foreach ($expectedLibrary as $key => $expectedValue) {
    assertPublicContract(
        ($library[$key] ?? null) === $expectedValue,
        'library.json changed public field ' . $key . '.'
    );
}

$expectedModules = $fixture['modules'] ?? null;
assertPublicContract(is_array($expectedModules), 'The public contract fixture is missing module contracts.');
$actualModuleDirectories = [];
foreach (glob($root . '/*/module.json') ?: [] as $modulePath) {
    $directory = basename(dirname($modulePath));
    $actualModuleDirectories[] = $directory;
    assertPublicContract(isset($expectedModules[$directory]), 'Unexpected module in public contract: ' . $directory);

    $module = readContractJson($modulePath);
    foreach ($expectedModules[$directory] as $key => $expectedValue) {
        assertPublicContract(
            ($module[$key] ?? null) === $expectedValue,
            $directory . '/module.json changed public field ' . $key . '.'
        );
    }
}
sort($actualModuleDirectories, SORT_STRING);
$expectedModuleDirectories = array_keys($expectedModules);
sort($expectedModuleDirectories, SORT_STRING);
assertPublicContract($actualModuleDirectories === $expectedModuleDirectories, 'A characterized module is missing.');

$expectedSources = $fixture['sources'] ?? null;
assertPublicContract(is_array($expectedSources), 'The public contract fixture is missing source contracts.');
foreach ($expectedSources as $relativePath => $expectedContract) {
    assertPublicContract(is_array($expectedContract), 'Invalid source contract for ' . $relativePath);
    foreach ($expectedContract as &$expectedItems) {
        assertPublicContract(is_array($expectedItems), 'Invalid source contract list for ' . $relativePath);
        sort($expectedItems, SORT_STRING);
    }
    unset($expectedItems);
    $actualContract = extractSourceContracts($root . '/' . $relativePath);
    assertPublicContract(
        $actualContract === $expectedContract,
        $relativePath . " changed its registered properties, variables, actions or public methods.\n"
        . 'Expected: ' . json_encode($expectedContract, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        . 'Actual:   ' . json_encode($actualContract, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}

$splitterSource = file_get_contents($root . '/SymconJSLive/module.php');
assertPublicContract($splitterSource !== false, 'Unable to read the splitter source.');
foreach ($fixture['hookPaths'] ?? [] as $hookPath) {
    assertPublicContract(
        is_string($hookPath) && str_contains($splitterSource, $hookPath),
        'The characterized hook path is missing: ' . (string) $hookPath
    );
}

echo 'JSLive public contracts are unchanged (' . count($expectedModules) . " modules).\n";
