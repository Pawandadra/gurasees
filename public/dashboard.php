<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();

$role = auth_user()['role'] ?? '';

if (in_array($role, ['receptionist', 'manager'], true)) {
    $errors = [];
    $old = patient_form_defaults();
    $existingPatientCode = null;
    $successMessage = flash_get('success');
    $errorMessage = flash_get('error');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_require();
        $result = Patient::register($_POST);

        if (request_wants_json()) {
            header('Content-Type: application/json; charset=utf-8');
            if ($result['ok']) {
                echo json_encode([
                    'ok' => true,
                    'patient_code' => $result['patient_code'],
                    'message' => __('patient.register.success', ['code' => $result['patient_code']]),
                ], JSON_THROW_ON_ERROR);
            } else {
                $payload = ['ok' => false, 'errors' => $result['errors']];
                if (!empty($result['existing_patient_code'])) {
                    $payload['existing_patient_code'] = $result['existing_patient_code'];
                }
                echo json_encode($payload, JSON_THROW_ON_ERROR);
            }
            exit;
        }

        if ($result['ok']) {
            flash_set('success', __('patient.register.success', ['code' => $result['patient_code']]));
            redirect(base_url('/dashboard.php'));
        }

        $errors = $result['errors'];
        $existingPatientCode = $result['existing_patient_code'] ?? null;
        $old = Patient::formStateFromRaw($_POST);
    }

    try {
        $recentPatients = Patient::lastRegistered();
        $dbError = false;
    } catch (Throwable) {
        $recentPatients = [];
        $dbError = true;
    }

    $viewData = compact('errors', 'old', 'existingPatientCode', 'successMessage', 'errorMessage', 'recentPatients', 'dbError');

    if ($role === 'manager') {
        view('dashboard/manager', $viewData);
    } else {
        view('receptionist/dashboard', $viewData);
    }
    exit;
}

if ($role === 'admin') {
    $pageTitle = __('role.admin');
    $activeNav = 'dashboard';
    $pageHeading = __('role.admin');
    require BASE_PATH . '/views/dashboard/coming_soon.php';
    exit;
}

http_response_code(403);
exit(__('error.forbidden'));
