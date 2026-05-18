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
    $_SESSION['_created'] = time();
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
    $until = (int) ($_SESSION['login_lock_until'] ?? 0);

    return $until > time();
}

function login_rate_limit_seconds(): int
{
    $until = (int) ($_SESSION['login_lock_until'] ?? 0);

    return max(0, $until - time());
}

function login_record_failure(): void
{
    $count = (int) ($_SESSION['login_fail_count'] ?? 0) + 1;
    $_SESSION['login_fail_count'] = $count;
    if ($count >= 5) {
        $_SESSION['login_lock_until'] = time() + 900;
        $_SESSION['login_fail_count'] = 0;
    }
}

function login_clear_failures(): void
{
    unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
}
