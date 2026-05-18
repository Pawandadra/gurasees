<?php

declare(strict_types=1);

/**
 * Escape output for HTML context (XSS prevention).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get application config value.
 *
 * @param array<string, mixed>|null $config
 */
function config(?string $key = null, mixed $default = null, ?array $config = null): mixed
{
    static $appConfig = null;
    if ($config !== null) {
        $appConfig = $config;
    }
    if ($appConfig === null) {
        $appConfig = require APP_PATH . '/config/app.php';
    }
    if ($key === null) {
        return $appConfig;
    }

    return $appConfig[$key] ?? $default;
}

/**
 * Redirect and stop execution.
 */
function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Base URL for links and assets.
 */
function base_url(string $path = ''): string
{
    $base = rtrim((string) config('url'), '/');
    $path = '/' . ltrim($path, '/');

    return $base === '' ? $path : $base . $path;
}

/**
 * Include a view with extracted data.
 *
 * @param array<string, mixed> $data
 */
function view(string $name, array $data = []): void
{
    $file = BASE_PATH . '/views/' . str_replace('.', '/', $name) . '.php';
    if (!is_readable($file)) {
        http_response_code(500);
        exit('View not found.');
    }
    extract($data, EXTR_SKIP);
    require $file;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return is_string($msg) ? $msg : null;
}