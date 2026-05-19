<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

csrf_require();

$code = strtoupper(input_string($_POST['code'] ?? '', 12));
$sortParams = Patient::normalizeSort(
    (string) ($_POST['sort'] ?? 'date'),
    (string) ($_POST['dir'] ?? 'desc')
);
$return = patient_return_from_request();
$listFilters = patient_list_filters_from_request();

if (Patient::delete($code)) {
    flash_set('success', __('patient.delete.success', ['code' => $code]));
} else {
    flash_set('error', __('patient.error.not_found'));
}

redirect(patient_return_url($return, $sortParams['sort'], $sortParams['dir'], $listFilters));
