<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string $code */
/** @var string $sort */
/** @var string $dir */
/** @var string $return */
/** @var array{q: string, gender: string, page: int} $listFilters */
/** @var string|null $existingPatientCode */

$return = $return ?? 'dashboard';
$listFilters = $listFilters ?? patient_list_filters_from_request();
$existingPatientCode = $existingPatientCode ?? null;
$phoneIso = (string) ($old['phone_iso'] ?? 'IN');
$phoneLocal = (string) ($old['phone_local'] ?? '');
$backUrl = patient_return_url($return, $sort, $dir, $listFilters);
ob_start();
?>
<div class="page-header-bar page-header-bar--inline mb-4">
    <?php $url = $backUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('patient.edit.title')) ?></h1>
</div>

<?php if (isset($errors['_form'])): ?>
    <div class="alert alert-danger"><?= e($errors['_form']) ?></div>
<?php endif; ?>

<?php if (isset($errors['_duplicate'])): ?>
    <?php require BASE_PATH . '/views/partials/patient_register_duplicate_alert.php'; ?>
<?php endif; ?>

<section class="reception-card reception-form">
    <p class="text-muted small mb-3"><?= e(__('patient.field.id')) ?>: <span class="patient-code"><?= e($code) ?></span></p>

    <form method="post" action="<?= e(base_url('/patient_edit.php')) ?>" id="patientEditForm" novalidate
          data-msg-required="<?= e(__('validation.required')) ?>"
          data-msg-address="<?= e(__('patient.error.address')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= e($code) ?>">
        <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <input type="hidden" name="dir" value="<?= e($dir) ?>">
        <?php if ($return === 'patients'): ?>
            <input type="hidden" name="return" value="patients">
            <?php foreach (patient_list_query_filters($listFilters) as $filterKey => $filterValue): ?>
                <?php if ($filterKey === 'return' || $filterValue === null || $filterValue === '') {
                    continue;
                } ?>
                <input type="hidden" name="<?= e((string) $filterKey) ?>" value="<?= e((string) $filterValue) ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <?php require BASE_PATH . '/views/partials/patient_form_row1.php'; ?>
        <?php require BASE_PATH . '/views/partials/patient_address_row.php'; ?>
        <?php require BASE_PATH . '/views/partials/patient_symptoms_fields.php'; ?>
        <?php require BASE_PATH . '/views/partials/patient_remarks_field.php'; ?>

        <div class="mt-3 d-flex gap-2">
            <button type="button" class="btn btn-reception-primary confirm-action-trigger" id="patientEditSubmitBtn"
                    data-confirm-title="<?= e(__('patient.edit.confirm_title')) ?>"
                    data-confirm="<?= e(__('patient.edit.confirm_message')) ?>"
                    data-confirm-label="<?= e(__('action.save')) ?>"
                    data-confirm-variant="primary">
                <?= e(__('action.save')) ?>
            </button>
            <a href="<?= e($backUrl) ?>" class="btn btn-outline-secondary"><?= e(__('action.cancel')) ?></a>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
$pageScripts = [
    'assets/js/phone-country.js',
    'assets/js/delivery-address.js',
    'assets/js/patient-gender-input.js',
    'assets/js/patient-symptoms-picker.js',
    'assets/js/patient-edit-form.js',
    'assets/js/form-enter-navigation.js',
];
require BASE_PATH . '/views/layouts/dashboard.php';
