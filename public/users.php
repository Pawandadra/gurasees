<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('User');

auth_require();
auth_require_role(['admin']);

$actingUser = auth_user();
$actingUserId = (int) ($actingUser['id'] ?? 0);

$errors = [];
$editErrors = [];
$editId = null;
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = input_string($_POST['action'] ?? '', 20);

    if ($action === 'add') {
        $result = User::create($_POST);
        if ($result['ok']) {
            flash_set('success', __('user.add.success'));
            redirect(base_url('/users.php'));
        }
        $errors = $result['errors'];
    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            flash_set('error', __('user.error.not_found'));
            redirect(base_url('/users.php'));
        }
        $result = User::update((int) $id, $_POST, $actingUserId);
        if ($result['ok']) {
            flash_set('success', __('user.edit.success'));
            redirect(base_url('/users.php'));
        }
        $editErrors = $result['errors'];
        $editId = (int) $id;
    } elseif ($action === 'set_active') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $active = filter_var($_POST['active'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || ($active !== 0 && $active !== 1)) {
            flash_set('error', __('user.error.not_found'));
            redirect(base_url('/users.php'));
        }
        $result = User::setActive((int) $id, $active === 1, $actingUserId);
        if ($result['ok']) {
            flash_set(
                'success',
                $active === 1 ? __('user.activate.success') : __('user.deactivate.success')
            );
        } else {
            $formError = $result['errors']['_form'] ?? __('user.error.not_found');
            flash_set('error', $formError);
        }
        redirect(base_url('/users.php'));
    }
}

try {
    $users = User::listForManage();
    $dbError = false;
} catch (Throwable) {
    $users = [];
    $dbError = true;
}

$pageTitle = __('user.manage.title');
$activeNav = 'users';

view('user/index', compact(
    'errors',
    'editErrors',
    'editId',
    'users',
    'actingUserId',
    'successMessage',
    'errorMessage',
    'dbError'
));
