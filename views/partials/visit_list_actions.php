<?php

declare(strict_types=1);

/** @var string $patientCode */
/** @var string $sort */
/** @var string $dir */
/** @var array<string, scalar|null> $actionExtra */

$actionExtra = $actionExtra ?? [];
$viewUrl = base_url('/patient_view.php?' . http_build_query(['code' => $patientCode]) . '&' . patient_action_query($sort, $dir, $actionExtra));
?>
<div class="patient-actions">
    <a href="<?= e($viewUrl) ?>" class="patient-action-btn patient-action-view"
       title="<?= e(__('visits.action.view_patient')) ?>" aria-label="<?= e(__('visits.action.view_patient')) ?>">
        <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
    </a>
</div>
