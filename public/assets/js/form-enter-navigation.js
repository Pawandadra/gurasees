(function () {
    'use strict';

    var FOCUSABLE =
        'input:not([type="hidden"]):not([disabled]), ' +
        'select:not([disabled]), ' +
        'textarea:not([disabled]), ' +
        'button:not([disabled])';

    function isVisible(el) {
        if (!el || el.disabled) {
            return false;
        }
        if (el.closest('[hidden]') || el.closest('.d-none')) {
            return false;
        }

        return el.getClientRects().length > 0;
    }

    function resolveTarget(el) {
        if (el.tagName === 'LABEL' && el.htmlFor) {
            var linked = document.getElementById(el.htmlFor);
            if (linked) {
                return linked;
            }
        }

        return el;
    }

    function focusableElements(form, options) {
        options = options || {};
        var seenRadio = {};
        var list = [];

        var skipSelectors = options.skipSelectors || [];

        form.querySelectorAll(FOCUSABLE).forEach(function (el) {
            if (el.classList.contains('phone-country-option')) {
                return;
            }

            for (var s = 0; s < skipSelectors.length; s++) {
                if (el.matches(skipSelectors[s])) {
                    return;
                }
            }

            if (!isVisible(el)) {
                return;
            }

            if (el.type === 'radio') {
                if (seenRadio[el.name]) {
                    return;
                }
                seenRadio[el.name] = true;

                var radios = form.querySelectorAll(
                    'input[type="radio"][name="' + CSS.escape(el.name) + '"]'
                );
                var pick = null;
                for (var i = 0; i < radios.length; i++) {
                    if (!isVisible(radios[i])) {
                        continue;
                    }
                    if (radios[i].checked) {
                        pick = radios[i];
                        break;
                    }
                    if (!pick) {
                        pick = radios[i];
                    }
                }
                if (pick) {
                    list.push(pick);
                }
                return;
            }

            list.push(el);
        });

        return list;
    }

    function indexInList(current, items) {
        if (current.type === 'radio') {
            for (var i = 0; i < items.length; i++) {
                if (items[i].type === 'radio' && items[i].name === current.name) {
                    return i;
                }
            }
            return -1;
        }

        return items.indexOf(current);
    }

    function focusNext(form, current, options) {
        options = options || {};
        var items = focusableElements(form, options);
        var idx = indexInList(current, items);
        if (idx < 0) {
            return;
        }

        var next = items[idx + 1];
        if (!next && options.cancelButtonSelector) {
            next = form.querySelector(options.cancelButtonSelector);
            if (next && !isVisible(next)) {
                next = null;
            }
        }
        if (!next && options.submitButtonSelector) {
            next = form.querySelector(options.submitButtonSelector);
            if (next && !isVisible(next)) {
                next = null;
            }
        }
        if (!next) {
            return;
        }

        next.focus();
        if (
            typeof next.select === 'function' &&
            (next.type === 'text' ||
                next.type === 'tel' ||
                next.type === 'number' ||
                next.type === 'search')
        ) {
            next.select();
        }
    }

    function dropdownOpen(inputId, resultsId) {
        if (!inputId || !resultsId) {
            return false;
        }

        var target = document.activeElement;
        if (!target || target.id !== inputId) {
            return false;
        }

        var results = document.getElementById(resultsId);
        return Boolean(results && !results.hidden);
    }

    function attachFormEnterNavigation(form, options) {
        if (!form) {
            return;
        }

        options = options || {};
        var skipDropdowns = options.skipDropdowns || [];
        var advanceFromPhoneCountry = Boolean(options.advanceFromPhoneCountry);

        form.addEventListener(
            'keydown',
            function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                if (event.defaultPrevented) {
                    return;
                }

                var target = resolveTarget(event.target);
                if (!form.contains(target)) {
                    return;
                }

                var picker = target.closest('.phone-country-picker');
                if (picker && picker.classList.contains('is-open')) {
                    return;
                }

                if (target.type === 'submit') {
                    if (options.submitOnEnter !== false) {
                        event.preventDefault();
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit(target);
                        } else {
                            form.submit();
                        }
                    }
                    return;
                }

                if (
                    options.cancelButtonSelector &&
                    target.matches(options.cancelButtonSelector)
                ) {
                    event.preventDefault();
                    event.stopPropagation();
                    target.click();
                    return;
                }

                if (
                    options.submitButtonSelector &&
                    target.matches(options.submitButtonSelector)
                ) {
                    event.preventDefault();
                    event.stopPropagation();
                    target.click();
                    return;
                }

                if (target.tagName === 'TEXTAREA') {
                    return;
                }

                for (var i = 0; i < skipDropdowns.length; i++) {
                    if (
                        dropdownOpen(
                            skipDropdowns[i].inputId,
                            skipDropdowns[i].resultsId
                        )
                    ) {
                        return;
                    }
                }

                event.preventDefault();
                event.stopPropagation();

                if (
                    advanceFromPhoneCountry &&
                    target.classList.contains('phone-country-trigger') &&
                    picker
                ) {
                    var phoneInput = picker
                        .closest('.phone-input-group')
                        ?.querySelector('input[name="phone"]');
                    if (phoneInput && isVisible(phoneInput)) {
                        phoneInput.focus();
                        if (typeof phoneInput.select === 'function') {
                            phoneInput.select();
                        }
                        return;
                    }
                }

                focusNext(form, target, options);
            },
            true
        );
    }

    window.attachFormEnterNavigation = attachFormEnterNavigation;

    var registerSection = document.getElementById('patient-register');
    if (registerSection) {
        attachFormEnterNavigation(registerSection.querySelector('form'), {
            advanceFromPhoneCountry: true,
            submitButtonSelector: '#patientRegisterSubmitBtn',
            skipSelectors: ['.patient-symptom-remove'],
            skipDropdowns: [
                {
                    inputId: 'patientSymptomSearchInput',
                    resultsId: 'patientSymptomSearchResults',
                },
            ],
        });
    }

    var editForm = document.getElementById('patientEditForm');
    if (editForm) {
        attachFormEnterNavigation(editForm, {
            advanceFromPhoneCountry: true,
            submitButtonSelector: '#patientEditSubmitBtn',
            skipSelectors: ['.patient-symptom-remove'],
            skipDropdowns: [
                {
                    inputId: 'patientSymptomSearchInput',
                    resultsId: 'patientSymptomSearchResults',
                },
            ],
        });
    }

    document.querySelectorAll('form.visit-log-form').forEach(function (form) {
        attachFormEnterNavigation(form, {
            cancelButtonSelector: '#patientVisitCancelBtn',
            submitButtonSelector: '#patientVisitSubmitBtn',
            submitOnEnter: false,
            skipDropdowns: [
                {
                    inputId: 'visitMedicineSearchInput',
                    resultsId: 'visitMedicineSearchResults',
                },
            ],
        });
    });
})();
