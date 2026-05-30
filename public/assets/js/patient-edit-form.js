(function () {
    'use strict';

    var form = document.getElementById('patientEditForm');
    if (!form) {
        return;
    }

    var requiredMsg = form.getAttribute('data-msg-required') || 'required';
    var addressMsg = form.getAttribute('data-msg-address') || 'Please enter a complete address.';

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
            input.closest('.patient-field') ||
            input.closest('.col-md-6') ||
            input.closest('[class*="col-"]') ||
            input.parentElement;
        if (wrap) {
            wrap.appendChild(feedback);
        } else {
            input.insertAdjacentElement('afterend', feedback);
        }
    }

    function normalizeGender(value) {
        var letter = String(value || '').trim().toUpperCase();
        if (letter === 'M') {
            return 'male';
        }
        if (letter === 'F') {
            return 'female';
        }
        if (letter === 'O') {
            return 'other';
        }

        return '';
    }

    function phoneIso() {
        var hidden = form.querySelector('input[name="phone_iso"]');
        return hidden ? String(hidden.value || 'IN').trim() : 'IN';
    }

    function validatePhone() {
        var phoneInput = fieldControl('phone');
        if (!phoneInput) {
            return false;
        }

        var local = String(phoneInput.value || '').replace(/\D+/g, '');
        var iso = phoneIso();
        if (iso === 'IN' && local.startsWith('0')) {
            local = local.slice(1);
        }

        if (local === '' || !/^\d+$/.test(local)) {
            return false;
        }

        if (local.length < 4 || local.length > 14) {
            return false;
        }

        if (iso === 'IN') {
            return local.length === 10 && local.charAt(0) >= '6' && local.charAt(0) <= '9';
        }

        return true;
    }

    function validateForm() {
        clearValidation();

        var valid = true;
        var firstInvalid = null;

        function fail(input) {
            if (!input) {
                return;
            }
            showFieldError(input, requiredMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = input;
            }
        }

        var nameInput = fieldControl('name');
        if (!nameInput || String(nameInput.value || '').trim().length < 2) {
            fail(nameInput);
        }

        var ageInput = fieldControl('age');
        var age = ageInput ? parseInt(ageInput.value, 10) : NaN;
        if (!Number.isFinite(age) || age < 1 || age > 120) {
            fail(ageInput);
        }

        var genderInput = fieldControl('gender');
        if (!genderInput || !normalizeGender(genderInput.value)) {
            fail(genderInput);
        }

        var phoneInput = fieldControl('phone');
        if (!validatePhone()) {
            fail(phoneInput);
        }

        var addressInput = fieldControl('address');
        if (!addressInput || String(addressInput.value || '').trim().length < 5) {
            showFieldError(addressInput, addressMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = addressInput;
            }
        }

        if (!valid && firstInvalid) {
            firstInvalid.focus();
            if (typeof firstInvalid.scrollIntoView === 'function') {
                firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }

        return valid;
    }

    form.addEventListener('submit', function (event) {
        if (!validateForm()) {
            event.preventDefault();
        }
    });

    var submitBtn = document.getElementById('patientEditSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener(
            'click',
            function (event) {
                if (!validateForm()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            },
            true
        );
    }
})();
