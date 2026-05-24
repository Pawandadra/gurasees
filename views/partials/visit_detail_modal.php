<?php

declare(strict_types=1);

/** @var string $code */
?>
<div class="modal fade" id="visitDetailModal" tabindex="-1" aria-labelledby="visitDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="visitDetailModalLabel"><?= e(__('visit.detail.title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('action.cancel')) ?>"></button>
            </div>
            <div class="modal-body pt-2" id="visitDetailModalBody">
                <p class="text-muted mb-0"><?= e(__('visit.detail.loading')) ?></p>
            </div>
            <div class="modal-footer border-0 pt-0 visit-detail-modal-footer d-none" id="visitDetailModalFooter">
                <form method="post" action="<?= e(base_url('/patient_view.php')) ?>" class="visit-delete-form me-auto" id="visitDeleteForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="code" value="<?= e($code) ?>">
                    <input type="hidden" name="action" value="delete_visit">
                    <input type="hidden" name="visit_id" value="" id="visitDeleteId">
                    <button type="button" class="btn btn-outline-danger confirm-action-trigger" id="visitDeleteBtn"
                            data-confirm-title="<?= e(__('visit.delete.confirm_title')) ?>"
                            data-confirm="<?= e(__('visit.delete.confirm')) ?>"
                            data-confirm-variant="danger"
                            data-confirm-label="<?= e(__('patient.action.delete')) ?>">
                        <?= e(__('patient.action.delete')) ?>
                    </button>
                </form>
                <div class="visit-detail-footer-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= e(__('action.cancel')) ?>
                    </button>
                    <a href="#" class="btn btn-reception-primary d-none" id="visitDetailEditBtn">
                        <?= e(__('patient.action.edit')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
