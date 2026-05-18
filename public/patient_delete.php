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
$sort = (string) ($_POST['sort'] ?? 'date');
$dir = (string) ($_POST['dir'] ?? 'desc');

if (Patient::delete($code)) {
    flash_set('success', __('patient.delete.success', ['code' => $code]));
} else {
    flash_set('error', __('patient.error.not_found'));
}

redirect(patient_dashboard_url($sort, $dir));
