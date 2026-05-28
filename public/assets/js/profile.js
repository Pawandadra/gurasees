(function () {
    'use strict';

    var form = document.querySelector('.profile-form');
    if (!form) {
        return;
    }

    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('password_confirm');
    var currentPassword = document.getElementById('current_password');

    var iconEye =
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" ' +
        'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>' +
        '<circle cx="12" cy="12" r="3"></circle></svg>';
    var iconEyeOff =
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" ' +
        'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M10.733 5.076A10.744 10.744 0 0 1 12 5c6.5 0 10 7 10 7a18.575 18.575 0 0 1-2.313 3.19"></path>' +
        '<path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path>' +
        '<path d="M17.479 17.499A10.75 10.75 0 0 1 12 19c-6.5 0-10-7-10-7a18.437 18.437 0 0 1 4.318-5.154"></path>' +
        '<path d="M2 2l20 20"></path></svg>';

    function syncToggleButton(btn, input) {
        if (!btn || !input) {
            return;
        }
        var isShown = input.type === 'text';
        btn.classList.toggle('is-on', isShown);
        btn.innerHTML = isShown ? iconEyeOff : iconEye;
    }

    function wantsPasswordChange() {
        var a = newPassword ? String(newPassword.value || '').trim() : '';
        var b = confirmPassword ? String(confirmPassword.value || '').trim() : '';
        var c = currentPassword ? String(currentPassword.value || '').trim() : '';
        return a !== '' || b !== '' || c !== '';
    }

    // Eye toggle
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.profile-password-toggle');
        if (!btn) {
            return;
        }

        var selector = btn.getAttribute('data-target');
        if (!selector) {
            return;
        }

        var input = document.querySelector(selector);
        if (!input || !(input instanceof HTMLInputElement)) {
            return;
        }

        input.type = input.type === 'password' ? 'text' : 'password';
        syncToggleButton(btn, input);
    });

    document.querySelectorAll('.profile-password-toggle').forEach(function (btn) {
        var selector = btn.getAttribute('data-target');
        if (!selector) {
            return;
        }
        var input = document.querySelector(selector);
        if (input && input instanceof HTMLInputElement) {
            syncToggleButton(btn, input);
        }
    });

    // Confirm only when changing password
    var confirming = false;
    form.addEventListener('submit', function (event) {
        if (confirming) {
            return;
        }

        if (!wantsPasswordChange()) {
            return;
        }

        var modalEl = document.getElementById('confirmActionModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
            return;
        }

        event.preventDefault();

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var titleEl = document.getElementById('confirmActionModalLabel');
        var bodyEl = document.getElementById('confirmActionModalBody');
        var confirmBtn = document.getElementById('confirmActionConfirmBtn');
        if (!titleEl || !bodyEl || !confirmBtn) {
            return;
        }

        titleEl.textContent = form.getAttribute('data-confirm-title') || 'Confirm?';
        bodyEl.textContent = form.getAttribute('data-confirm-message') || '';
        confirmBtn.textContent = form.getAttribute('data-confirm-label') || 'Confirm';
        confirmBtn.classList.remove('btn-danger', 'btn-reception-primary', 'btn-primary');
        confirmBtn.classList.add('btn-danger');

        var handler = function () {
            confirming = true;
            confirmBtn.removeEventListener('click', handler);
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
            modal.hide();
        };

        confirmBtn.addEventListener('click', handler, { once: true });
        modal.show();
    });
})();

