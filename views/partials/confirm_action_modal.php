<div class="modal fade" id="confirmActionModal" tabindex="-1"
     aria-labelledby="confirmActionModalLabel" aria-hidden="true"
     data-default-title="<?= e(__('action.confirm_title')) ?>"
     data-default-confirm-label="<?= e(__('action.confirm')) ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger" id="confirmActionModalLabel"><?= e(__('action.confirm_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?= e(__('action.cancel')) ?>"></button>
            </div>
            <div class="modal-body pt-2" id="confirmActionModalBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" id="confirmActionCancelBtn" data-bs-dismiss="modal">
                    <?= e(__('action.cancel')) ?>
                </button>
                <button type="button" class="btn btn-danger" id="confirmActionConfirmBtn">
                    <?= e(__('action.confirm')) ?>
                </button>
            </div>
        </div>
    </div>
</div>
