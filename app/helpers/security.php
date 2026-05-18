<?php

declare(strict_types=1);

/**
 * Secure session initialization.
 *
 * @param array<string, mixed> $appConfig
 */
function session_bootstrap(array $appConfig): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $lifetime = (int) ($appConfig['session_lifetime'] ?? 7200);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', (string) $lifetime);

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_name('GAA_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();

    if (!isset($_SESSION['_created'])) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    } elseif (time() - (int) $_SESSION['_created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

/**
 * HTTP security headers.
 */
function security_send_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        . "font-src 'self' https://cdn.jsdelivr.net; "
        . "img-src 'self' data:; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );
}

/**
 * Generate or return CSRF token (stored in session).
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Hidden input for CSRF protection in forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST request.
 */
function csrf_verify(): bool
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '') {
        return false;
    }

    return isset($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Require valid CSRF or abort with 403.
 */
function csrf_require(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        exit(__('error.csrf'));
    }
}

/**
 * Sanitize string input.
 */
function input_string(mixed $value, int $maxLength = 255): string
{
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    if (mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }

    return $value;
}