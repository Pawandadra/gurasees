<?php

declare(strict_types=1);

/** @var string $patientCode */
/** @var int $visitId */
?>
<div class="patient-actions">
    <?php if ($visitId > 0): ?>
        <button type="button" class="patient-action-btn patient-action-view visit-detail-trigger"
                data-visit-id="<?= e((string) $visitId) ?>"
                data-patient-code="<?= e($patientCode) ?>"
                title="<?= e(__('visit.detail.view')) ?>"
                aria-label="<?= e(__('visit.detail.view')) ?>">
            <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
        </button>
    <?php endif; ?>
</div>
