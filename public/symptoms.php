<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Symptom');

auth_require();
auth_require_role(['manager', 'admin']);

$errors = [];
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = input_string($_POST['action'] ?? '', 20);

    if ($action === 'add') {
        $result = Symptom::create((string) ($_POST['label'] ?? ''));
        if ($result['ok']) {
            flash_set('success', __('symptom.add.success'));
            redirect(base_url('/symptoms.php'));
        }
        $errors = $result['errors'];
    } elseif ($action === 'remove') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            flash_set('error', __('symptom.error.not_found'));
        } elseif (Symptom::isAssignedToPatient((int) $id)) {
            flash_set('error', __('symptom.error.in_use'));
        } elseif (Symptom::deactivate((int) $id)) {
            flash_set('success', __('symptom.delete.success'));
        } else {
            flash_set('error', __('symptom.error.not_found'));
        }
        redirect(base_url('/symptoms.php'));
    }
}

try {
    $symptoms = Symptom::listForManage();
    $dbError = false;
} catch (Throwable) {
    $symptoms = [];
    $dbError = true;
}

$pageTitle = __('symptom.manage.title');
$activeNav = 'symptoms';

view('symptom/index', compact('errors', 'symptoms', 'successMessage', 'errorMessage', 'dbError'));
