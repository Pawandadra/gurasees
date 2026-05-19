<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Courier');
load_model('Visit');

auth_require();
auth_require_role(['manager', 'admin']);

$pageTitle = __('courier.list.title');
$activeNav = 'courier';
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dispatch') {
    csrf_require();
    $visitId = filter_var($_POST['visit_id'] ?? '', FILTER_VALIDATE_INT);
    $user = auth_user();
    $userId = $user !== null ? (int) $user['id'] : 0;

    if ($visitId !== false && Courier::dispatch((int) $visitId, $userId)) {
        flash_set('success', __('courier.dispatch.success'));
    } else {
        flash_set('error', __('courier.dispatch.error'));
    }
    redirect(base_url('/courier.php'));
}

try {
    $courierRows = Courier::listPending();
    $dbError = false;
} catch (Throwable) {
    $courierRows = [];
    $dbError = true;
}

view('courier/index', compact('pageTitle', 'activeNav', 'courierRows', 'dbError', 'successMessage', 'errorMessage'));
