<?php

declare(strict_types=1);

/** @var array<string, string> $GLOBALS['__lang_strings'] */
$GLOBALS['__lang_strings'] = [];

/**
 * Load English UI strings.
 *
 * @param array<string, mixed> $appConfig
 */
function lang_init(array $appConfig): void
{
    unset($appConfig);

    /** @var array<string, string> $strings */
    $strings = require APP_PATH . '/lang/en.php';
    $GLOBALS['__lang_strings'] = $strings;
}

/**
 * Translate a key. Optional :placeholder replacement.
 *
 * @param array<string, string|int> $replace
 */
function __(string $key, array $replace = []): string
{
    $text = $GLOBALS['__lang_strings'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}
