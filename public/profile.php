<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('User');

auth_require();

$user = auth_user();
$userId = (int) ($user['id'] ?? 0);
if ($userId < 1) {
    auth_logout();
    redirect(base_url('/login.php'));
}

$pageTitle = __('profile.title');
$activeNav = 'profile';
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$errors = [];
$old = [
    'name' => (string) ($user['name'] ?? ''),
    'current_password' => '',
    'new_password' => '',
    'password_confirm' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $result = User::updateProfile($userId, $_POST);
    if ($result['ok']) {
        $_SESSION['user_name'] = $result['name'];
        if (($result['changed'] ?? false) === true) {
            flash_set('success', __('profile.success'));
        }
        redirect(base_url('/profile.php'));
    }

    $errors = $result['errors'];
    $old['name'] = trim((string) ($_POST['name'] ?? ''));
}

view('profile/index', compact(
    'pageTitle',
    'activeNav',
    'successMessage',
    'errorMessage',
    'errors',
    'old'
));

