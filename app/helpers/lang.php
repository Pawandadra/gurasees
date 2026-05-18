<?php

declare(strict_types=1);

/** @var array<string, string> $GLOBALS['__lang_strings'] */
$GLOBALS['__lang_strings'] = [];

/** @var string $GLOBALS['__lang_locale'] */
$GLOBALS['__lang_locale'] = 'en';

/**
 * Initialize language from session / query (whitelisted only).
 *
 * @param array<string, mixed> $appConfig
 */
function lang_init(array $appConfig): void
{
    $supported = $appConfig['supported_locales'] ?? ['en', 'pa'];
    $default = $appConfig['default_locale'] ?? 'en';

    if (
        isset($_GET['lang'])
        && is_string($_GET['lang'])
        && in_array($_GET['lang'], $supported, true)
    ) {
        $_SESSION['locale'] = $_GET['lang'];
    }

    $locale = $_SESSION['locale'] ?? $default;
    if (!in_array($locale, $supported, true)) {
        $locale = $default;
    }

    lang_set($locale);
}

/**
 * Set active locale and load its string file.
 */
function lang_set(string $locale): void
{
    $file = APP_PATH . '/lang/' . $locale . '.php';
    if (!is_readable($file)) {
        $file = APP_PATH . '/lang/en.php';
        $locale = 'en';
    }

    /** @var array<string, string> $strings */
    $strings = require $file;
    $GLOBALS['__lang_strings'] = $strings;
    $GLOBALS['__lang_locale'] = $locale;
    $_SESSION['locale'] = $locale;
}

/**
 * Current locale code (en | pa).
 */
function locale(): string
{
    return $GLOBALS['__lang_locale'];
}

/**
 * Translate a key. Optional :placeholder replacement.
 *
 * Example: __('welcome.title') or __('greeting', ['name' => 'Raj'])
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

/**
 * Build language switch URL for a given locale.
 */
function lang_url(string $locale): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!str_starts_with($path, '/')) {
        $path = '/';
    }

    $query = $_GET;
    $query['lang'] = $locale;

    return $path . '?' . http_build_query($query);
}
