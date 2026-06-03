(function () {
    'use strict';

    var form = document.getElementById('patientTotalBalanceForm');
    var input = document.getElementById('patientTotalBalanceInput');
    var statusEl = document.getElementById('patientTotalBalanceStatus');

    if (!form || !input) {
        return;
    }

    var saving = false;
    var saveRequest = null;
    var statusTimer = null;
    var lastSaved = normalizeAmount(input.value);

    var msgSaving = form.getAttribute('data-msg-saving') || 'Saving…';
    var msgSaved = form.getAttribute('data-msg-saved') || 'Saved';
    var msgError = form.getAttribute('data-msg-error') || 'Could not save';

    function normalizeAmount(value) {
        var parsed = parseFloat(String(value || '').trim());
        return Number.isFinite(parsed) ? Math.max(0, Math.round(parsed * 100) / 100) : NaN;
    }

    function formatAmount(value) {
        return value.toFixed(2);
    }

    function setStatus(message) {
        if (!statusEl) {
            return;
        }

        if (statusTimer) {
            window.clearTimeout(statusTimer);
            statusTimer = null;
        }

        if (!message) {
            statusEl.textContent = '';
            statusEl.hidden = true;
            return;
        }

        statusEl.textContent = message;
        statusEl.hidden = false;
    }

    function setDueState(amount) {
        form.classList.toggle('patient-view-total-balance--due', amount > 0);
    }

    function parseResponse(response) {
        return response.text().then(function (text) {
            if (!text) {
                return { ok: false, errors: { _form: msgError } };
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                return { ok: false, errors: { _form: msgError } };
            }
        });
    }

    function saveBalance() {
        if (saving) {
            return;
        }

        var amount = normalizeAmount(input.value);
        if (!Number.isFinite(amount)) {
            input.classList.add('is-invalid');
            setStatus('');
            return;
        }

        input.classList.remove('is-invalid');

        if (amount === lastSaved) {
            input.value = formatAmount(amount);
            setStatus('');
            return;
        }

        saving = true;
        setStatus(msgSaving);

        if (saveRequest) {
            saveRequest.abort();
        }

        saveRequest = new AbortController();

        var body = new FormData(form);
        body.set('total_balance', formatAmount(amount));

        fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: body,
            signal: saveRequest.signal,
        })
            .then(function (response) {
                return parseResponse(response).then(function (data) {
                    if (!response.ok || !data.ok) {
                        throw data;
                    }

                    return data;
                });
            })
            .then(function (data) {
                var saved = normalizeAmount(data.total_balance);
                if (!Number.isFinite(saved)) {
                    saved = amount;
                }

                lastSaved = saved;
                input.value = formatAmount(saved);
                input.classList.remove('is-invalid');
                setDueState(saved);
                setStatus(msgSaved);
                statusTimer = window.setTimeout(function () {
                    setStatus('');
                }, 1500);
            })
            .catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }

                var message = msgError;
                if (error && error.errors && error.errors.total_balance) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    if (error && error.errors && error.errors._form) {
                        message = error.errors._form;
                    }
                }
                setStatus(message);
            })
            .finally(function () {
                saving = false;
                saveRequest = null;
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveBalance();
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveBalance();
        }
    });

    input.addEventListener('focusout', function () {
        saveBalance();
    });
})();
