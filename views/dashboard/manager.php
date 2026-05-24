<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var list<array<string, mixed>> $recentPatients */
/** @var bool $dbError */

$pageTitle = __('role.manager');

ob_start();
?>
<?php require BASE_PATH . '/views/partials/patient_register_dashboard.php'; ?>
<?php
$content = ob_get_clean();
$activeNav = 'dashboard';
load_model('PaymentSettings');
$pageScripts = [
    'assets/js/phone-country.js',
    'assets/js/delivery-address.js',
    'assets/js/patient-gender-input.js',
    'assets/js/patient-symptoms-picker.js',
    'assets/js/form-enter-navigation.js',
    'assets/js/patient-register-confirm.js',
    'assets/js/gst-inclusive.js',
    'assets/js/payment-fields.js',
];
require BASE_PATH . '/views/layouts/dashboard.php';
