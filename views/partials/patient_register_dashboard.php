<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var list<array<string, mixed>> $recentPatients */
/** @var bool $dbError */
/** @var string|null $existingPatientCode */
$errors = $errors ?? [];
$existingPatientCode = $existingPatientCode ?? null;
$old = $old ?? patient_form_defaults();
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$recentPatients = $recentPatients ?? [];
$dbError = $dbError ?? false;
$phoneIso = (string) ($old['phone_iso'] ?? 'IN');
$phoneLocal = (string) ($old['phone_local'] ?? '');
$recentColumns = [
    'id' => __('patient.field.id'),
    'name' => __('patient.field.name'),
    'age' => __('patient.field.age'),
    'gender' => __('patient.field.gender'),
    'phone' => __('patient.field.phone'),
    'address' => __('patient.field.address'),
    'date' => __('patient.field.last_visited'),
];
?>
<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success reception-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['_form'])): ?>
        <div class="alert alert-danger"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['_duplicate'])): ?>
        <?php require BASE_PATH . '/views/partials/patient_register_duplicate_alert.php'; ?>
    <?php endif; ?>

    <section class="reception-card reception-form mb-4" id="patient-register">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('patient.register.title')) ?></h2>

        <form method="post" action="<?= e(base_url('/dashboard.php')) ?>" id="patientRegisterForm" novalidate
              data-msg-required="<?= e(__('validation.required')) ?>"
              data-msg-address="<?= e(__('patient.error.address')) ?>"
              data-msg-confirm="<?= e(__('patient.register.confirm_message')) ?>"
              data-msg-success="<?= e(__('patient.register.success_message')) ?>"
              data-msg-duplicate="<?= e(__('patient.register.duplicate')) ?>"
              data-msg-additional-phone-same="<?= e(__('patient.error.additional_phone_same')) ?>"
              data-view-existing="<?= e(__('patient.register.view_existing')) ?>"
              data-patient-view-base="<?= e(base_url('/patient_view.php')) ?>">
            <?= csrf_field() ?>

            <?php
            $showRegisteredAt = true;
            require BASE_PATH . '/views/partials/patient_form_row1.php';
            ?>
            <?php require BASE_PATH . '/views/partials/patient_address_row.php'; ?>
            <?php
            if (PaymentSettings::isEnabled()) {
                require BASE_PATH . '/views/partials/patient_payment_row.php';
            }
            ?>
            <?php require BASE_PATH . '/views/partials/patient_symptoms_fields.php'; ?>
            <?php require BASE_PATH . '/views/partials/patient_remarks_field.php'; ?>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-reception-primary" id="patientRegisterSubmitBtn"><?= e(__('patient.register.submit')) ?></button>
                <a href="<?= e(base_url('/dashboard.php')) ?>" class="btn btn-outline-secondary"><?= e(__('patient.register.clear')) ?></a>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('reception.dashboard.last_registered')) ?></h2>
        <?php if ($recentPatients === []): ?>
            <p class="text-muted mb-0"><?= e(__('reception.dashboard.empty')) ?></p>
        <?php else: ?>
            <?php
            $patientRows = $recentPatients;
            $patientColumns = $recentColumns;
            $listPath = '/dashboard.php';
            $listFilters = [];
            $return = 'dashboard';
            $actionExtra = [];
            $emptyMessage = '';
            $sort = 'date';
            $dir = 'desc';
            $tableSortable = false;
            require BASE_PATH . '/views/partials/patient_list_table.php';
            ?>
        <?php endif; ?>
    </section>

    <?php require BASE_PATH . '/views/partials/patient_register_confirm_modal.php'; ?>

<?php endif; ?>
