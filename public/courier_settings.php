<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('CourierSettings');

auth_require();
auth_require_role(['manager', 'admin']);

$errors = [];
$successMessage = flash_get('success');
$returnPath = trim((string) ($_GET['return'] ?? '/courier.php'));
if (!str_starts_with($returnPath, '/courier')) {
    $returnPath = '/courier.php';
}
$returnUrl = base_url($returnPath);
$old = CourierSettings::formDefaults();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $returnUrl = courier_settings_return_url();
    $result = CourierSettings::save($_POST);

    if ($result['ok']) {
        flash_set('success', __('courier.settings.success'));
        redirect($returnUrl);
    }

    $errors = $result['errors'];
    $returnPath = trim((string) ($_POST['return'] ?? '/courier.php'));
    if (!str_starts_with($returnPath, '/courier')) {
        $returnPath = '/courier.php';
    }
    $returnUrl = base_url($returnPath);
    $old = [
        'courier_sender_name' => trim((string) ($_POST['courier_sender_name'] ?? '')),
        'courier_sender_phone' => trim((string) ($_POST['courier_sender_phone'] ?? '')),
        'courier_sender_address' => trim((string) ($_POST['courier_sender_address'] ?? '')),
    ];
}

$pageTitle = __('courier.settings.title');
$activeNav = 'courier';

view('courier/settings', compact('errors', 'old', 'successMessage', 'returnUrl', 'returnPath'));
