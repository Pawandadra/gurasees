(function () {
    'use strict';

    var modalEl = document.getElementById('patientDeleteModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var bodyEl = document.getElementById('patientDeleteModalBody');
    var confirmBtn = document.getElementById('patientDeleteConfirmBtn');
    var pendingForm = null;

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.patient-delete-trigger');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        pendingForm = trigger.closest('form');
        if (!pendingForm) {
            return;
        }

        var message = trigger.getAttribute('data-confirm');
        if (message && bodyEl) {
            bodyEl.textContent = message;
        }

        modal.show();
    });

    confirmBtn.addEventListener('click', function () {
        if (pendingForm) {
            pendingForm.submit();
        }
        modal.hide();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pendingForm = null;
    });
})();
