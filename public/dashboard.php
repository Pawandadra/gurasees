<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();

$role = auth_user()['role'] ?? '';

if ($role === 'receptionist') {
    $errors = [];
    $old = patient_form_defaults();
    $successMessage = flash_get('success');
    $errorMessage = flash_get('error');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_require();
        $result = Patient::register($_POST);

        if ($result['ok']) {
            flash_set('success', __('patient.register.success', ['code' => $result['patient_code']]));
            redirect(base_url('/dashboard.php'));
        }

        $errors = $result['errors'];
        $old = Patient::formStateFromRaw($_POST);
    }

    $sortParams = Patient::normalizeSort(
        (string) ($_GET['sort'] ?? 'date'),
        (string) ($_GET['dir'] ?? 'desc')
    );

    view('receptionist/dashboard', array_merge(
        compact('errors', 'old', 'successMessage', 'errorMessage'),
        $sortParams
    ));
    exit;
}

if ($role === 'manager') {
    view('dashboard/manager');
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
