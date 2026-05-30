(function () {
    'use strict';

    var form = document.getElementById('patientRegisterForm');
    var modalEl = document.getElementById('patientRegisterConfirmModal');
    if (!form || !modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var titleEl = document.getElementById('patientRegisterConfirmModalLabel');
    var bodyEl = document.getElementById('patientRegisterConfirmModalBody');
    var closeBtn = document.getElementById('patientRegisterModalCloseBtn');
    var confirmFooter = document.getElementById('patientRegisterConfirmFooter');
    var successFooter = document.getElementById('patientRegisterSuccessFooter');
    var confirmBtn = document.getElementById('patientRegisterConfirmBtn');
    var okBtn = document.getElementById('patientRegisterOkBtn');
    var cancelBtn = confirmFooter
        ? confirmFooter.querySelector('[data-bs-dismiss="modal"]')
        : null;
    var paymentSection = document.getElementById('patientPaymentSection');

    if (!titleEl || !bodyEl || !confirmFooter || !successFooter || !confirmBtn || !okBtn) {
        return;
    }

    var requiredMsg = form.getAttribute('data-msg-required') || 'required';
    var addressMsg = form.getAttribute('data-msg-address') || 'Please enter a complete address.';
    var confirmMsg = form.getAttribute('data-msg-confirm') || '';
    var successTemplate = form.getAttribute('data-msg-success') || ':code';
    var confirmTitle = modalEl.getAttribute('data-confirm-title') || '';
    var successTitle = modalEl.getAttribute('data-success-title') || '';
    var submitting = false;

    function fieldControl(name) {
        var el = form.elements.namedItem(name);
        return el instanceof HTMLElement ? el : null;
    }

    function fieldWrapper(input) {
        return (
            input.closest('.patient-field') ||
            input.closest('.payment-field') ||
            input.closest('.col-md-6') ||
            input.closest('[class*="col-"]')
        );
    }

    function clearValidation() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback.client-validation').forEach(function (el) {
            el.remove();
        });
        form.querySelectorAll('.client-form-error, .patient-duplicate-alert').forEach(function (el) {
            el.remove();
        });
    }

    function showFieldError(name, message) {
        var input = fieldControl(name);
        if (!input) {
            return;
        }

        input.classList.add('is-invalid');

        var inputGroup = input.closest('.input-group');
        if (inputGroup) {
            inputGroup.classList.add('is-invalid');
        }

        var feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block client-validation';
        feedback.textContent = message;

        var wrap = fieldWrapper(input);
        if (wrap) {
            wrap.appendChild(feedback);
        } else {
            input.insertAdjacentElement('afterend', feedback);
        }
    }

    function showFormError(message) {
        var alert = document.createElement('div');
        alert.className = 'alert alert-danger client-form-error';
        alert.textContent = message;
        form.insertBefore(alert, form.firstChild);
    }

    function showDuplicateError(patientCode) {
        clearValidation();

        var nameInput = fieldControl('name');
        var phoneInput = fieldControl('phone');
        if (nameInput) {
            nameInput.classList.add('is-invalid');
        }
        if (phoneInput) {
            phoneInput.classList.add('is-invalid');
            var phoneGroup = phoneInput.closest('.input-group');
            if (phoneGroup) {
                phoneGroup.classList.add('is-invalid');
            }
        }

        var alert = document.createElement('div');
        alert.className = 'alert alert-danger client-form-error patient-duplicate-alert';
        alert.setAttribute('role', 'alert');

        var message = form.getAttribute('data-msg-duplicate') || 'Patient already exists.';
        alert.appendChild(document.createTextNode(message + ' '));

        if (patientCode) {
            var viewLabel = form.getAttribute('data-view-existing') || 'View patient profile';
            var baseUrl = form.getAttribute('data-patient-view-base') || '/patient_view.php';
            var link = document.createElement('a');
            link.href = baseUrl + '?code=' + encodeURIComponent(patientCode);
            link.className = 'alert-link fw-semibold';
            link.textContent = viewLabel + ' (' + patientCode + ')';
            alert.appendChild(link);
        }

        form.insertBefore(alert, form.firstChild);

        if (nameInput) {
            nameInput.focus();
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

    function parsePaymentAmount() {
        var amountInput = fieldControl('payment_amount');
        if (!amountInput) {
            return 0;
        }

        var value = parseFloat(amountInput.value);
        return Number.isFinite(value) ? Math.max(0, value) : 0;
    }

    function gstPercent() {
        if (!paymentSection) {
            return 0;
        }

        return parseFloat(paymentSection.getAttribute('data-gst-percent') || '0') || 0;
    }

    function registrationTotal(net) {
        var percent = gstPercent();
        if (typeof window.gstSplitInclusive === 'function') {
            return window.gstSplitInclusive(net, percent).total;
        }
        return net;
    }

    function paymentDetailsVisible() {
        if (!paymentSection) {
            return false;
        }

        var col = paymentSection.querySelector('.payment-detail-col');
        return col ? !col.classList.contains('d-none') : parsePaymentAmount() > 0;
    }

    function validateForm() {
        clearValidation();
        var valid = true;
        var firstInvalid = null;

        function fail(name) {
            showFieldError(name, requiredMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = fieldControl(name);
            }
        }

        var nameInput = fieldControl('name');
        if (!nameInput || String(nameInput.value || '').trim().length < 2) {
            fail('name');
        }

        var ageInput = fieldControl('age');
        var age = ageInput ? parseInt(ageInput.value, 10) : NaN;
        if (!Number.isFinite(age) || age < 1 || age > 120) {
            fail('age');
        }

        if (!normalizeGender(fieldControl('gender')?.value)) {
            fail('gender');
        }

        if (!validatePhone()) {
            fail('phone');
        }

        var addressInput = fieldControl('address');
        if (!addressInput || String(addressInput.value || '').trim().length < 5) {
            showFieldError('address', addressMsg);
            valid = false;
            if (!firstInvalid) {
                firstInvalid = addressInput;
            }
        }

        var registeredAtInput = fieldControl('registered_at');
        var registeredAt = registeredAtInput ? String(registeredAtInput.value || '').trim() : '';
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        if (registeredAt === '') {
            fail('registered_at');
        } else {
            var parts = registeredAt.split('-');
            if (parts.length !== 3) {
                fail('registered_at');
            } else {
                var picked = new Date(
                    parseInt(parts[0], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[2], 10)
                );
                picked.setHours(0, 0, 0, 0);
                if (Number.isNaN(picked.getTime()) || picked > today) {
                    fail('registered_at');
                }
            }
        }

        if (paymentSection && paymentDetailsVisible()) {
            var amount = parsePaymentAmount();
            if (amount <= 0) {
                fail('payment_amount');
            } else {
                var method = fieldControl('payment_method');
                var status = fieldControl('payment_status');
                if (!method || !method.value) {
                    fail('payment_method');
                }
                if (!status || !status.value) {
                    fail('payment_status');
                }
                if (status && status.value === 'partial') {
                    var paidInput = fieldControl('payment_paid_amount');
                    var paid = paidInput ? parseFloat(paidInput.value) : NaN;
                    var totalDue = registrationTotal(amount);
                    if (
                        !Number.isFinite(paid) ||
                        paid <= 0 ||
                        paid >= totalDue
                    ) {
                        fail('payment_paid_amount');
                    }
                }
            }
        }

        if (!valid && firstInvalid) {
            firstInvalid.focus();
            if (typeof firstInvalid.select === 'function' && firstInvalid.type !== 'select-one') {
                firstInvalid.select();
            }
        }

        return valid;
    }

    function showConfirmState() {
        titleEl.textContent = confirmTitle;
        bodyEl.textContent = confirmMsg;
        bodyEl.classList.remove('patient-register-success-body');
        confirmFooter.classList.remove('d-none');
        successFooter.classList.add('d-none');
        if (closeBtn) {
            closeBtn.classList.remove('d-none');
        }
    }

    function showSuccessState(patientCode) {
        titleEl.textContent = successTitle;
        bodyEl.textContent = successTemplate.replace(':code', patientCode);
        bodyEl.classList.add('patient-register-success-body');
        confirmFooter.classList.add('d-none');
        successFooter.classList.remove('d-none');
        if (closeBtn) {
            closeBtn.classList.add('d-none');
        }
        okBtn.focus();
    }

    function focusModalPrimary() {
        if (!successFooter.classList.contains('d-none')) {
            okBtn.focus();
            return;
        }

        confirmBtn.focus();
    }

    function applyServerErrors(errors, existingPatientCode) {
        if (errors._duplicate) {
            showDuplicateError(existingPatientCode || null);
            return;
        }

        clearValidation();
        var firstInvalid = null;

        Object.keys(errors).forEach(function (key) {
            if (key === '_form') {
                showFormError(errors[key]);
                return;
            }

            showFieldError(key, requiredMsg);
            if (!firstInvalid) {
                firstInvalid = fieldControl(key);
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
        }
    }

    function submitRegistration() {
        if (submitting) {
            return;
        }

        submitting = true;
        confirmBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.data && result.data.ok && result.data.patient_code) {
                    showSuccessState(result.data.patient_code);
                    return;
                }

                modal.hide();
                applyServerErrors(
                    (result.data && result.data.errors) || { _form: 'Error' },
                    result.data && result.data.existing_patient_code
                );
            })
            .catch(function () {
                modal.hide();
                showFormError('Error');
            })
            .finally(function () {
                submitting = false;
                confirmBtn.disabled = false;
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!validateForm()) {
            return;
        }

        showConfirmState();
        modal.show();
    });

    confirmBtn.addEventListener('click', function () {
        if (!validateForm()) {
            modal.hide();
            return;
        }

        submitRegistration();
    });

    okBtn.addEventListener('click', function () {
        modal.hide();
    });

    modalEl.addEventListener('shown.bs.modal', focusModalPrimary);

    modalEl.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || !modalEl.classList.contains('show')) {
            return;
        }

        if (!successFooter.classList.contains('d-none')) {
            event.preventDefault();
            okBtn.click();
            return;
        }

        event.preventDefault();

        if (document.activeElement === cancelBtn) {
            confirmBtn.focus();
            return;
        }

        if (document.activeElement !== confirmBtn) {
            confirmBtn.focus();
            return;
        }

        confirmBtn.click();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (!successFooter.classList.contains('d-none')) {
            window.location.reload();
            return;
        }

        showConfirmState();
    });
})();
