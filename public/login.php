<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('User');

if (auth_check()) {
    auth_redirect_dashboard();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_require_form()) {
        $errors['_form'] = __('error.csrf');
    } elseif (login_rate_limited()) {
        $errors['_form'] = __('auth.error.locked', ['seconds' => (string) login_rate_limit_seconds()]);
    } elseif (!captcha_verify((string) ($_POST['captcha'] ?? ''))) {
        $errors['captcha'] = __('auth.error.captcha');
    } else {
        $result = User::attemptLogin(
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );

        if ($result['ok']) {
            auth_login($result['user']);
            login_clear_failures();
            auth_redirect_dashboard();
        }

        login_record_failure();
        if (!isset($errors['_form'])) {
            $errors['_form'] = __('auth.error.invalid');
        }
    }
}

view('auth/login', compact('errors'));
