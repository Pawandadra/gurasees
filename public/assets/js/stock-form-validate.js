(function () {
    'use strict';

    var form = document.querySelector('.stock-add-form');
    if (!form) {
        return;
    }

    var requiredMsg = form.getAttribute('data-msg-required') || 'required';

    function fieldControl(name) {
        var el = form.elements.namedItem(name);
        return el instanceof HTMLElement ? el : null;
    }

    function clearValidation() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback.client-validation').forEach(function (el) {
            el.remove();
        });
    }

    function showFieldError(input, message) {
        input.classList.add('is-invalid');

        var inputGroup = input.closest('.input-group');
        if (inputGroup) {
            inputGroup.classList.add('is-invalid');
        }

        var feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block client-validation';
        feedback.textContent = message;

        var wrap =
            input.closest('.col-6') ||
            input.closest('.col-12') ||
            input.closest('[class*="col-"]') ||
            input.parentElement;
        if (wrap) {
            wrap.appendChild(feedback);
        } else {
            input.insertAdjacentElement('afterend', feedback);
        }
    }

    function isEmptyValue(input) {
        if (input instanceof HTMLInputElement && input.type === 'file') {
            return !input.files || input.files.length === 0;
        }

        return String(input.value || '').trim() === '';
    }

    function validateItems() {
        var names = form.querySelectorAll('input[name="item_names[]"]');
        var quantities = form.querySelectorAll('input[name="item_quantities[]"]');
        var amounts = form.querySelectorAll('input[name="item_amounts[]"]');
        var hasLine = false;

        for (var i = 0; i < names.length; i++) {
            var name = String(names[i].value || '').trim();
            if (name === '') {
                continue;
            }
            hasLine = true;
            var qty = parseFloat(quantities[i] ? quantities[i].value : '');
            var amount = parseFloat(amounts[i] ? amounts[i].value : '');
            if (!Number.isFinite(qty) || qty <= 0 || !Number.isFinite(amount) || amount <= 0) {
                return false;
            }
        }

        return hasLine;
    }

    form.addEventListener('submit', function (event) {
        clearValidation();

        var valid = true;
        var firstInvalid = null;

        ['bill_number', 'register_number', 'bill_date', 'supplier'].forEach(function (name) {
            var input = fieldControl(name);
            if (!input || !isEmptyValue(input)) {
                return;
            }
            showFieldError(input, requiredMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = input;
            }
        });

        var fileInput = fieldControl('bill_file');
        if (fileInput && isEmptyValue(fileInput)) {
            showFieldError(fileInput, requiredMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = fileInput;
            }
        }

        if (!validateItems()) {
            var itemsBlock = document.getElementById('stockItemList');
            if (itemsBlock) {
                var alert = document.createElement('div');
                alert.className = 'invalid-feedback d-block client-validation';
                alert.textContent = requiredMsg;
                itemsBlock.insertAdjacentElement('beforebegin', alert);
            }
            valid = false;
            if (!firstInvalid) {
                firstInvalid = form.querySelector('input[name="item_names[]"]');
            }
        }

        if (!valid) {
            event.preventDefault();
            if (firstInvalid && typeof firstInvalid.focus === 'function') {
                firstInvalid.focus();
            }
        }
    });
})();
