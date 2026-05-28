(function () {
    'use strict';

    var modalEl = document.getElementById('confirmActionModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var titleEl = document.getElementById('confirmActionModalLabel');
    var bodyEl = document.getElementById('confirmActionModalBody');
    var cancelBtn = document.getElementById('confirmActionCancelBtn');
    var confirmBtn = document.getElementById('confirmActionConfirmBtn');
    var pendingForm = null;

    if (!titleEl || !bodyEl || !cancelBtn || !confirmBtn) {
        return;
    }

    var defaultTitle = modalEl.getAttribute('data-default-title') || 'Confirm?';
    var defaultConfirmLabel = modalEl.getAttribute('data-default-confirm-label') || 'Confirm';

    function applyConfirmButtonStyle(variant) {
        confirmBtn.classList.remove('btn-danger', 'btn-reception-primary', 'btn-primary');
        if (variant === 'primary' || variant === 'success') {
            confirmBtn.classList.add('btn-reception-primary');
            titleEl.classList.remove('text-danger');
            titleEl.classList.add('text-success');
        } else {
            confirmBtn.classList.add('btn-danger');
            titleEl.classList.remove('text-success');
            titleEl.classList.add('text-danger');
        }
    }

    function resetModalDefaults() {
        titleEl.textContent = defaultTitle;
        bodyEl.textContent = '';
        confirmBtn.textContent = defaultConfirmLabel;
        applyConfirmButtonStyle('danger');
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.confirm-action-trigger');
        if (!trigger || trigger.disabled) {
            return;
        }

        event.preventDefault();
        pendingForm = trigger.closest('form');
        if (!pendingForm) {
            return;
        }

        var title = trigger.getAttribute('data-confirm-title') || defaultTitle;
        var message = trigger.getAttribute('data-confirm') || '';
        var confirmLabel = trigger.getAttribute('data-confirm-label') || defaultConfirmLabel;
        var variant = trigger.getAttribute('data-confirm-variant') || 'danger';

        titleEl.textContent = title;
        bodyEl.textContent = message;
        confirmBtn.textContent = confirmLabel;
        applyConfirmButtonStyle(variant);

        modal.show();
    });

    confirmBtn.addEventListener('click', function () {
        if (pendingForm) {
            // For forms using custom validation (novalidate), skip native constraint checks
            // and let the form's submit handler show inline errors.
            var usesCustomValidation = pendingForm.hasAttribute('novalidate');
            if (!usesCustomValidation) {
                if (typeof pendingForm.reportValidity === 'function' && !pendingForm.reportValidity()) {
                    modal.hide();
                    return;
                }
            }
            if (typeof pendingForm.requestSubmit === 'function') {
                pendingForm.requestSubmit();
            } else {
                pendingForm.submit();
            }
        }
        modal.hide();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        cancelBtn.focus();
    });

    modalEl.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || !modalEl.classList.contains('show')) {
            return;
        }

        if (document.activeElement === confirmBtn) {
            event.preventDefault();
            confirmBtn.click();
            return;
        }

        event.preventDefault();

        if (document.activeElement === cancelBtn) {
            cancelBtn.click();
            return;
        }

        cancelBtn.focus();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pendingForm = null;
        resetModalDefaults();
    });
})();
