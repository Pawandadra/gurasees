<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('User');

auth_require();
auth_require_role(['admin']);

$errors = [];
$editErrors = [];
$successMessage = flash_get('success');
$errorMessage = flash_get('error');
$currentUser = auth_user();

$editId = filter_var($_GET['edit_id'] ?? '', FILTER_VALIDATE_INT);
if ($editId === false) {
    $editId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = input_string($_POST['action'] ?? '', 30);

    if ($action === 'add') {
        $result = User::create($_POST);

        if ($result['ok']) {
            flash_set('success', __('users.success.created'));
            redirect(base_url('/users.php'));
        }

        $errors = $result['errors'];
    }

    if ($action === 'update') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            flash_set('error', __('users.error.invalid_user'));
            redirect(base_url('/users.php'));
        }

        if ($currentUser !== null && (int) $currentUser['id'] === (int) $id) {
            $_POST['is_active'] = '1';
            $_POST['role'] = (string) ($currentUser['role'] ?? 'admin');
        }

        $result = User::update((int) $id, $_POST);

        if ($result['ok']) {
            flash_set('success', __('users.success.updated'));
            redirect(base_url('/users.php'));
        }

        $editErrors[(int) $id] = $result['errors'];
        $editId = (int) $id;
    }

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            flash_set('error', __('users.error.invalid_user'));
            redirect(base_url('/users.php'));
        }

        if ($currentUser !== null && (int) $currentUser['id'] === (int) $id) {
            flash_set('error', __('users.error.self_delete'));
            redirect(base_url('/users.php'));
        }

        $result = User::deleteById((int) $id);

        if ($result['ok']) {
            flash_set('success', __('users.success.deleted'));
        } else {
            flash_set('error', $result['error'] ?? __('users.error.delete_failed'));
        }

        redirect(base_url('/users.php'));
    }
}

try {
    $users = User::listAll();
    $dbError = false;
} catch (Throwable) {
    $users = [];
    $dbError = true;
}

$pageTitle = __('nav.users');
$activeNav = 'users';

view('users/index', compact(
    'errors',
    'editErrors',
    'users',
    'successMessage',
    'errorMessage',
    'dbError',
    'currentUser',
    'editId',
    'pageTitle',
    'activeNav'
));