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
$return = patient_return_from_request();
$listFilters = patient_list_filters_from_request();

$errors = [];
$old = Patient::recordToForm($patient);
$existingPatientCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $user = auth_user();
    $editedBy = $user !== null ? (int) $user['id'] : null;
    $result = Patient::update($code, $_POST, $editedBy);

    if ($result['ok']) {
        flash_set('success', __('patient.update.success', ['code' => $code]));
        redirect(patient_view_url($code, $return, $sortParams['sort'], $sortParams['dir'], $listFilters));
    }

    $errors = $result['errors'];
    $old = Patient::formStateFromRaw($_POST);
    $existingPatientCode = $result['existing_patient_code'] ?? null;
}

$pageTitle = __('patient.edit.title');
$activeNav = $return === 'patients' ? 'patients' : 'dashboard';

view('patient/edit', array_merge(compact('errors', 'old', 'code', 'return', 'listFilters', 'existingPatientCode'), $sortParams));
