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

$old = array_merge(
    GstSettings::formDefaults(),
    [
        'default_amount' => PaymentSettings::formatAmount(PaymentSettings::defaultAmount()),
        'default_method' => PaymentSettings::defaultMethod(),
        'default_status' => PaymentSettings::defaultStatus(),
        'visit_default_charge' => VisitSettings::formatCharge(VisitSettings::defaultCharge()),
    ]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $paymentResult = PaymentSettings::saveDefaults($_POST);
    $gstResult = GstSettings::save($_POST);
    $visitResult = VisitSettings::save($_POST);

    if ($paymentResult['ok'] && $gstResult['ok'] && $visitResult['ok']) {
        flash_set('success', __('payment.settings.success'));
        redirect(base_url('/payment_settings.php'));
    }

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
        'visit_default_charge' => trim((string) ($_POST['visit_default_charge'] ?? '0')),
    ];
}

$pageTitle = __('payment.settings.title');
$activeNav = 'payment_settings';
$paymentEnabled = PaymentSettings::isEnabled();

view('payment/settings', compact('errors', 'old', 'successMessage', 'paymentEnabled'));
