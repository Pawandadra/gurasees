<?php

declare(strict_types=1);

/** @var int $visitId */
/** @var string $courierStatus */

$courierStatus = $courierStatus ?? Courier::STATUS_PENDING;
$isPending = $courierStatus === Courier::STATUS_PENDING;
$canPrint = $courierStatus !== Courier::STATUS_CANCELED;
$viewQuery = array_merge(
    ['visit_id' => (int) $visitId],
    isset($sort, $dir)
        ? patient_build_list_query($sort, $dir, courier_list_query_filters($listFilters ?? []))
        : []
);
?>
<div class="patient-actions courier-actions">
    <a href="<?= e(base_url('/courier_view.php?' . http_build_query($viewQuery))) ?>"
       class="patient-action-btn patient-action-view"
       title="<?= e(__('courier.action.view')) ?>"
       aria-label="<?= e(__('courier.action.view')) ?>">
        <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
    </a>
    <?php if ($canPrint): ?>
        <a href="<?= e(base_url('/courier_label.php?visit_id=' . (int) $visitId)) ?>"
           class="patient-action-btn patient-action-print"
           target="_blank" rel="noopener"
           title="<?= e(__('courier.action.print')) ?>"
           aria-label="<?= e(__('courier.action.print')) ?>">
            <?php require BASE_PATH . '/views/partials/icons/print.php'; ?>
        </a>
    <?php endif; ?>
    <?php if ($isPending): ?>
        <form method="post" action="<?= e(base_url('/courier.php')) ?>" class="patient-action-delete-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="dispatch">
            <input type="hidden" name="visit_id" value="<?= (int) $visitId ?>">
            <?php if (isset($sort, $dir, $listFilters)): require BASE_PATH . '/views/partials/courier_list_preserve.php'; endif; ?>
            <button type="button"
                    class="patient-action-btn patient-action-dispatch confirm-action-trigger"
                    data-confirm-title="<?= e(__('courier.dispatch.confirm_title')) ?>"
                    data-confirm="<?= e(__('courier.dispatch.confirm')) ?>"
                    data-confirm-label="<?= e(__('courier.dispatch.submit')) ?>"
                    data-confirm-variant="primary"
                    title="<?= e(__('courier.action.dispatch')) ?>"
                    aria-label="<?= e(__('courier.action.dispatch')) ?>">
                <?php require BASE_PATH . '/views/partials/icons/courier.php'; ?>
            </button>
        </form>
        <form method="post" action="<?= e(base_url('/courier.php')) ?>" class="patient-action-delete-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="visit_id" value="<?= (int) $visitId ?>">
            <?php if (isset($sort, $dir, $listFilters)): require BASE_PATH . '/views/partials/courier_list_preserve.php'; endif; ?>
            <button type="button"
                    class="patient-action-btn patient-action-cancel confirm-action-trigger"
                    data-confirm-title="<?= e(__('courier.cancel.confirm_title')) ?>"
                    data-confirm="<?= e(__('courier.cancel.confirm')) ?>"
                    data-confirm-label="<?= e(__('courier.action.cancel')) ?>"
                    title="<?= e(__('courier.action.cancel')) ?>"
                    aria-label="<?= e(__('courier.action.cancel')) ?>">
                <?php require BASE_PATH . '/views/partials/icons/cancel.php'; ?>
            </button>
        </form>
    <?php endif; ?>
</div>
