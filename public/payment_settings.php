<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('PaymentSettings');
load_model('GstSettings');
load_model('VisitSettings');
auth_require();
auth_require_role(['manager', 'admin']);

$errors = [];
$successMessage = flash_get('success');
$returnPath = trim((string) ($_GET['return'] ?? '/payments.php'));
if (!str_starts_with($returnPath, '/payment')) {
    $returnPath = '/payments.php';
}
$returnUrl = base_url($returnPath);

$old = array_merge(
    [
        'default_amount' => PaymentSettings::formatAmount(PaymentSettings::defaultAmount()),
        'default_method' => PaymentSettings::defaultMethod(),
        'default_status' => PaymentSettings::defaultStatus(),
    ],
    VisitSettings::formDefaults(),
    GstSettings::formDefaults()
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $paymentResult = PaymentSettings::saveDefaults($_POST);
    $gstResult = GstSettings::save($_POST);
    $visitResult = VisitSettings::save($_POST);

    if ($paymentResult['ok'] && $gstResult['ok'] && $visitResult['ok']) {
        flash_set('success', __('payment.settings.success'));
        redirect(payment_settings_return_url());
    }

    $returnPath = trim((string) ($_POST['return'] ?? '/payments.php'));
    if (!str_starts_with($returnPath, '/payment')) {
        $returnPath = '/payments.php';
    }
    $returnUrl = base_url($returnPath);

    $errors = array_merge(
        $paymentResult['ok'] ? [] : $paymentResult['errors'],
        $gstResult['ok'] ? [] : $gstResult['errors'],
        $visitResult['ok'] ? [] : $visitResult['errors']
    );
    $old = [
        'default_amount' => trim((string) ($_POST['default_amount'] ?? '0')),
        'default_method' => input_string($_POST['default_method'] ?? 'cash', 10),
        'default_status' => input_string($_POST['default_status'] ?? 'paid', 10),
        'gst_registration_percent' => trim((string) ($_POST['gst_registration_percent'] ?? '')),
        'gst_visit_percent' => trim((string) ($_POST['gst_visit_percent'] ?? '')),
        'gst_medicine_percent' => trim((string) ($_POST['gst_medicine_percent'] ?? '')),
        'gst_courier_percent' => trim((string) ($_POST['gst_courier_percent'] ?? '')),
        'visit_default_charge' => trim((string) ($_POST['visit_default_charge'] ?? '0')),
        'visit_default_method' => input_string($_POST['visit_default_method'] ?? 'cash', 10),
        'visit_default_status' => input_string($_POST['visit_default_status'] ?? 'paid', 10),
    ];
}

$pageTitle = __('payment.settings.title');
$activeNav = 'payments';

view('payment/settings', compact('errors', 'old', 'successMessage', 'returnUrl', 'returnPath'));
