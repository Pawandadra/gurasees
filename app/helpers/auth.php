<?php

declare(strict_types=1);

function auth_check(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * @return array{id: int, role: string, name: string}|null
 */
function auth_user(): ?array
{
    if (!auth_check()) {
        return null;
    }

    return [
        'id' => (int) ($_SESSION['user_id'] ?? 0),
        'role' => (string) ($_SESSION['user_role'] ?? ''),
        'name' => (string) ($_SESSION['user_name'] ?? ''),
    ];
}

/**
 * @param array{id: int, name: string, role: string} $user
 */
function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['_session_started'] = time();
    $_SESSION['_user_refreshed_at'] = time();
    csrf_rotate();
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_require(): void
{
    if (!auth_check()) {
        redirect(base_url('/login.php'));
    }

    if (!auth_refresh_session()) {
        auth_logout();
        redirect(base_url('/login.php'));
    }
}

function auth_refresh_session(): bool
{
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id < 1) {
        return false;
    }

    $lastRefresh = (int) ($_SESSION['_user_refreshed_at'] ?? 0);
    if ($lastRefresh > 0 && time() - $lastRefresh < 90) {
        if (!class_exists('User', false)) {
            load_model('User');
        }

        return User::isActive($id);
    }

    if (!class_exists('User', false)) {
        load_model('User');
    }

    $user = User::findById($id);
    if ($user === null || !$user['is_active']) {
        return false;
    }

    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['_user_refreshed_at'] = time();

    return true;
}

/**
 * @param list<string> $roles
 */
function auth_require_role(array $roles): void
{
    auth_require();
    $user = auth_user();
    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit(__('error.forbidden'));
    }
}

function auth_redirect_dashboard(): never
{
    redirect(base_url('/dashboard.php'));
}

function auth_role_label(string $role): string
{
    $key = 'role.' . $role;

    return __($key);
}

function login_rate_limited(): bool
{
    $data = login_rate_read();

    return (int) ($data['lock_until'] ?? 0) > time();
}

function login_rate_limit_seconds(): int
{
    $data = login_rate_read();
    $until = (int) ($data['lock_until'] ?? 0);

    return max(0, $until - time());
}

function login_record_failure(): void
{
    $data = login_rate_read();
    $count = (int) ($data['count'] ?? 0) + 1;
    $lockUntil = (int) ($data['lock_until'] ?? 0);

    if ($count >= 5) {
        $lockUntil = time() + 900;
        $count = 0;
    }

    login_rate_write([
        'count' => $count,
        'lock_until' => $lockUntil,
    ]);
}

function login_clear_failures(): void
{
    $file = login_rate_file();
    if (is_file($file)) {
        @unlink($file);
    }

    unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
}

/**
 * @return array{count: int, lock_until: int}
 */
function login_rate_read(): array
{
    $file = login_rate_file();
    if (!is_readable($file)) {
        return ['count' => 0, 'lock_until' => 0];
    }

    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return ['count' => 0, 'lock_until' => 0];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['count' => 0, 'lock_until' => 0];
    }

    return [
        'count' => max(0, (int) ($data['count'] ?? 0)),
        'lock_until' => max(0, (int) ($data['lock_until'] ?? 0)),
    ];
}

/**
 * @param array{count: int, lock_until: int} $data
 */
function login_rate_write(array $data): void
{
    $file = login_rate_file();
    file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX);
}

function login_rate_file(): string
{
    $dir = BASE_PATH . '/storage/cache/login';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    return $dir . '/' . hash('sha256', $username . '|' . $ip);
}
