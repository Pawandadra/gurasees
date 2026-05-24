<?php

declare(strict_types=1);

/** Minimum session and CSRF lifetime (2 hours). */
function session_lifetime_seconds(array $appConfig): int
{
    return max(7200, (int) ($appConfig['session_lifetime'] ?? 7200));
}

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

    $lifetime = session_lifetime_seconds($appConfig);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', (string) $lifetime);
    ini_set('session.cookie_lifetime', (string) $lifetime);

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_name('GAA_SESSID');
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    $_SESSION['_session_lifetime'] = $lifetime;

    if (!isset($_SESSION['_session_started'])) {
        session_regenerate_id(true);
        $_SESSION['_session_started'] = time();
    } elseif (time() - (int) $_SESSION['_session_started'] >= $lifetime) {
        // Full session rotation after lifetime; keep old session until the new one is saved.
        session_regenerate_id(false);
        $_SESSION['_session_started'] = time();
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

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    if ($isHttps && (string) config('env') !== 'local') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Allow only safe in-app return paths (path + query, no scheme/host).
 *
 * @param list<string> $allowedPaths Exact paths such as "/payments.php"
 */
function safe_return_path(string $return, array $allowedPaths): ?string
{
    $return = trim($return);
    if ($return === '' || !str_starts_with($return, '/') || str_contains($return, '://')) {
        return null;
    }

    $path = parse_url($return, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || str_contains($path, '..')) {
        return null;
    }

    if (!in_array($path, $allowedPaths, true)) {
        return null;
    }

    return $return;
}

/**
 * Issue a new CSRF token for the current session.
 */
function csrf_rotate(): string
{
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['_csrf_issued'] = time();

    return $_SESSION['_csrf_token'];
}

/**
 * Generate or return CSRF token (stored in session).
 */
function csrf_token(): string
{
    $lifetime = max(7200, (int) ($_SESSION['_session_lifetime'] ?? 7200));
    $issued = (int) ($_SESSION['_csrf_issued'] ?? 0);

    if (
        empty($_SESSION['_csrf_token'])
        || $issued === 0
        || time() - $issued > $lifetime
    ) {
        return csrf_rotate();
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
 * Handle invalid CSRF on a form page: rotate token and return false (caller re-renders the form).
 */
function csrf_require_form(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    if (csrf_verify()) {
        return true;
    }

    csrf_rotate();

    return false;
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