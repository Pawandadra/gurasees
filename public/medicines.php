<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Medicine');

auth_require();
auth_require_role(['manager', 'admin']);

$errors = [];
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = input_string($_POST['action'] ?? '', 20);

    if ($action === 'add') {
        $result = Medicine::create($_POST);
        if ($result['ok']) {
            flash_set('success', __('medicine.add.success'));
            redirect(base_url('/medicines.php'));
        }
        $errors = $result['errors'];
    } elseif ($action === 'remove') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id !== false && Medicine::deactivate((int) $id)) {
            flash_set('success', __('medicine.delete.success'));
        } else {
            flash_set('error', __('medicine.error.not_found'));
        }
        redirect(base_url('/medicines.php'));
    }
}

try {
    $medicines = Medicine::listForManage();
    $dbError = false;
} catch (Throwable) {
    $medicines = [];
    $dbError = true;
}

$pageTitle = __('medicine.manage.title');
$activeNav = 'medicines';

view('medicine/index', compact('errors', 'medicines', 'successMessage', 'errorMessage', 'dbError'));
