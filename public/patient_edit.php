<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$code = strtoupper(input_string($_GET['code'] ?? $_POST['code'] ?? '', 12));
$patient = Patient::findByCode($code);
if ($patient === null) {
    http_response_code(404);
    exit(__('patient.error.not_found'));
}

$sortParams = Patient::normalizeSort(
    (string) ($_GET['sort'] ?? $_POST['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? $_POST['dir'] ?? 'desc')
);

$errors = [];
$old = Patient::recordToForm($patient);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $result = Patient::update($code, $_POST);

    if ($result['ok']) {
        flash_set('success', __('patient.update.success', ['code' => $code]));
        redirect(patient_dashboard_url($sortParams['sort'], $sortParams['dir']));
    }

    $errors = $result['errors'];
    $old = Patient::formStateFromRaw($_POST);
}

$pageTitle = __('patient.edit.title');
$activeNav = 'dashboard';

view('patient/edit', array_merge(compact('errors', 'old', 'code'), $sortParams));
