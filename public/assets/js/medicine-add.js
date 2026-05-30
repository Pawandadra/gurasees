(function () {
    'use strict';

    var form = document.getElementById('medicineAddForm');
    if (!form) {
        return;
    }

    var trigger = form.querySelector('.confirm-action-trigger');
    var nameInput = document.getElementById('medicine_name');
    var confirmTemplate = form.getAttribute('data-confirm-message') || '';
    var confirmModal = document.getElementById('confirmActionModal');
    var confirmBtn = document.getElementById('confirmActionConfirmBtn');
    var confirmPending = false;

    if (!trigger || !nameInput) {
        return;
    }

    function updateConfirmMessage() {
        var name = nameInput.value.trim();
        if (confirmTemplate && name !== '') {
            trigger.setAttribute('data-confirm', confirmTemplate.replace(':name', name));
        }
    }

    trigger.addEventListener(
        'click',
        function (event) {
            if (!form.reportValidity()) {
                event.stopImmediatePropagation();
                confirmPending = false;
                return;
            }

            updateConfirmMessage();
            confirmPending = true;
        },
        true
    );

    if (confirmModal) {
        confirmModal.addEventListener('hidden.bs.modal', function () {
            confirmPending = false;
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener(
            'click',
            function () {
                if (confirmPending) {
                    form.dataset.confirmSubmit = '1';
                    confirmPending = false;
                }
            },
            true
        );
    }

    form.addEventListener(
        'submit',
        function (event) {
            if (form.dataset.confirmSubmit === '1') {
                delete form.dataset.confirmSubmit;
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (!form.reportValidity()) {
                return;
            }

            updateConfirmMessage();
            confirmPending = true;
            trigger.click();
        },
        true
    );
})();
