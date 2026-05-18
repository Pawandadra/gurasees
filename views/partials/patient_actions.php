<?php

declare(strict_types=1);

/** @var string $patientCode */
/** @var string $sort */
/** @var string $dir */

$query = patient_action_query($sort, $dir);
$viewUrl = base_url('/patient_view.php?' . http_build_query(['code' => $patientCode]) . '&' . $query);
$editUrl = base_url('/patient_edit.php?' . http_build_query(['code' => $patientCode]) . '&' . $query);
?>
<div class="patient-actions">
    <a href="<?= e($viewUrl) ?>" class="patient-action-btn patient-action-view"
       title="<?= e(__('patient.action.view')) ?>" aria-label="<?= e(__('patient.action.view')) ?>">
        <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
    </a>
    <a href="<?= e($editUrl) ?>" class="patient-action-btn patient-action-edit"
       title="<?= e(__('patient.action.edit')) ?>" aria-label="<?= e(__('patient.action.edit')) ?>">
        <?php require BASE_PATH . '/views/partials/icons/edit.php'; ?>
    </a>
    <form method="post" action="<?= e(base_url('/patient_delete.php')) ?>" class="patient-action-delete-form">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= e($patientCode) ?>">
        <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <input type="hidden" name="dir" value="<?= e($dir) ?>">
        <button type="button" class="patient-action-btn patient-action-delete patient-delete-trigger"
                data-confirm="<?= e(__('patient.delete.confirm', ['code' => $patientCode])) ?>"
                title="<?= e(__('patient.action.delete')) ?>" aria-label="<?= e(__('patient.action.delete')) ?>">
            <?php require BASE_PATH . '/views/partials/icons/delete.php'; ?>
        </button>
    </form>
</div>
