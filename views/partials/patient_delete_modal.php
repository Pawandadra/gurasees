<div class="modal fade" id="patientDeleteModal" tabindex="-1" aria-labelledby="patientDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger" id="patientDeleteModalLabel"><?= e(__('patient.delete.confirm_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('action.cancel')) ?>"></button>
            </div>
            <div class="modal-body pt-2" id="patientDeleteModalBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(__('action.cancel')) ?></button>
                <button type="button" class="btn btn-danger" id="patientDeleteConfirmBtn"><?= e(__('patient.action.delete')) ?></button>
            </div>
        </div>
    </div>
</div>
