<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('User');

auth_require();

$currentUser = auth_user();

if ($currentUser === null) {
    redirect(base_url('/login.php'));
}

$profileErrors = [];
$passwordErrors = [];
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$userId = (int) $currentUser['id'];
$canChangeRole = (string) $currentUser['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = input_string($_POST['action'] ?? '', 40);

    if ($action === 'update_profile') {
        $result = User::updateOwnProfile($userId, $_POST, $canChangeRole);

        if ($result['ok']) {
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_role'] = $result['user']['role'];

            flash_set('success', __('profile.success.updated'));
            redirect(base_url('/profile.php'));
        }

        $profileErrors = $result['errors'];
    }

    if ($action === 'change_password') {
        $result = User::changeOwnPassword(
            $userId,
            is_string($_POST['old_password'] ?? null) ? (string) $_POST['old_password'] : '',
            is_string($_POST['new_password'] ?? null) ? (string) $_POST['new_password'] : '',
            is_string($_POST['new_password_confirm'] ?? null) ? (string) $_POST['new_password_confirm'] : ''
        );

        if ($result['ok']) {
            flash_set('success', __('profile.success.password_changed'));
            redirect(base_url('/profile.php'));
        }

        $passwordErrors = $result['errors'];
    }
}

$profileUser = User::findById($userId);

if ($profileUser === null) {
    http_response_code(404);
    exit(__('users.error.user_not_found'));
}

$pageTitle = __('nav.profile');
$activeNav = 'profile';

view('profile/index', compact(
    'profileUser',
    'profileErrors',
    'passwordErrors',
    'successMessage',
    'errorMessage',
    'canChangeRole',
    'pageTitle',
    'activeNav'
));