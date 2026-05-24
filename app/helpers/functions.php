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
 * Base URL from the current HTTP request (scheme + Host header).
 */
function request_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ? 'https'
        : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

    return $scheme . '://' . ($host !== '' ? $host : 'localhost');
}

/**
 * Resolved application base URL without a trailing slash.
 */
function app_base_url(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $configured = rtrim((string) config('url'), '/');
    if ($configured === '' || $configured === 'auto') {
        $resolved = request_base_url();

        return $resolved;
    }

    // Local dev: follow the Host the browser used (localhost, LAN IP, hostname).
    if (config('env') === 'local' && trim((string) ($_SERVER['HTTP_HOST'] ?? '')) !== '') {
        $resolved = request_base_url();

        return $resolved;
    }

    $resolved = $configured;

    return $resolved;
}

/**
 * Base URL for links and assets.
 */
function base_url(string $path = ''): string
{
    $base = app_base_url();
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
        error_log('View not found: ' . $name);
        http_response_code(500);
        if ((bool) config('debug')) {
            exit('View not found.');
        }
        app_respond_error(500);
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

function request_wants_json(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return str_contains($accept, 'application/json');
}

/**
 * Write one CSV row (PHP 8.4+ requires explicit escape for fputcsv).
 *
 * @param resource $handle
 * @param list<scalar|null> $fields
 */
function csv_put_row($handle, array $fields): void
{
    $normalized = [];
    foreach ($fields as $field) {
        if (is_int($field) || is_float($field)) {
            $normalized[] = (string) $field;
        } elseif (is_bool($field)) {
            $normalized[] = $field ? '1' : '0';
        } else {
            $normalized[] = (string) ($field ?? '');
        }
    }

    fputcsv($handle, $normalized, ',', '"', '\\');
}

/**
 * Fetch a paginated list; re-query once if the requested page is past the last page.
 *
 * @template T of array{rows: list<mixed>, total: int}
 * @param callable(int): T $fetch
 * @return array{result: T, page: int}
 */
function list_paginate(callable $fetch, int $page, int $perPage): array
{
    $page = max(1, $page);
    $result = $fetch($page);
    $total = (int) ($result['total'] ?? 0);
    if ($total < 1) {
        return ['result' => $result, 'page' => 1];
    }

    $totalPages = max(1, (int) ceil($total / $perPage));
    $clamped = min($page, $totalPages);
    if ($clamped !== $page) {
        $result = $fetch($clamped);
    }

    return ['result' => $result, 'page' => $clamped];
}