<div class="modal fade" id="patientRegisterConfirmModal" tabindex="-1"
     aria-labelledby="patientRegisterConfirmModalLabel" aria-hidden="true"
     data-confirm-title="<?= e(__('patient.register.confirm_title')) ?>"
     data-success-title="<?= e(__('patient.register.success_title')) ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="patientRegisterConfirmModalLabel">
                    <?= e(__('patient.register.confirm_title')) ?>
                </h5>
                <button type="button" class="btn-close" id="patientRegisterModalCloseBtn"
                        data-bs-dismiss="modal" aria-label="<?= e(__('action.cancel')) ?>"></button>
            </div>
            <div class="modal-body pt-2" id="patientRegisterConfirmModalBody">
                <?= e(__('patient.register.confirm_message')) ?>
            </div>
            <div class="modal-footer border-0 pt-0" id="patientRegisterConfirmFooter">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= e(__('action.cancel')) ?>
                </button>
                <button type="button" class="btn btn-reception-primary" id="patientRegisterConfirmBtn">
                    <?= e(__('patient.register.submit')) ?>
                </button>
            </div>
            <div class="modal-footer border-0 pt-0 d-none" id="patientRegisterSuccessFooter">
                <button type="button" class="btn btn-reception-primary" id="patientRegisterOkBtn">
                    <?= e(__('action.ok')) ?>
                </button>
            </div>
        </div>
    </div>
</div>
