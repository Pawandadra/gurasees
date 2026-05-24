<?php

declare(strict_types=1);

/** @var string|null $existingPatientCode */

if (empty($existingPatientCode)) {
    return;
}

$profileUrl = base_url('/patient_view.php?' . http_build_query(['code' => $existingPatientCode]));
?>
<div class="alert alert-danger patient-duplicate-alert mb-3" role="alert">
    <?= e(__('patient.register.duplicate')) ?>
    <a href="<?= e($profileUrl) ?>" class="alert-link fw-semibold ms-1">
        <?= e(__('patient.register.view_existing')) ?> (<?= e($existingPatientCode) ?>)
    </a>
</div>
