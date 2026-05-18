(function () {
    'use strict';

    var section = document.getElementById('patientPaymentSection');
    var amountInput = document.getElementById('payment_amount');
    var detailCols = section ? section.querySelectorAll('.payment-detail-col') : [];
    var partialField = document.getElementById('paymentPartialField');
    var paidInput = document.getElementById('payment_paid_amount');
    var methodSelect = document.getElementById('payment_method');
    var statusInputs = document.querySelectorAll('.payment-status-input');
    var gstSummary = document.getElementById('paymentGstSummary');

    if (!amountInput || !detailCols.length) {
        return;
    }

    var gstPercent = 0;
    if (section) {
        gstPercent = parseFloat(section.getAttribute('data-gst-percent') || '0') || 0;
    }

    function parseAmount() {
        var value = parseFloat(amountInput.value);
        return Number.isFinite(value) ? value : 0;
    }

    function gstAmount(base) {
        if (base <= 0 || gstPercent <= 0) {
            return 0;
        }
        return Math.round(base * gstPercent * 100) / 10000;
    }

    function formatMoney(value) {
        return '₹' + Number(value).toFixed(2);
    }

    function updateGstSummary() {
        if (!gstSummary) {
            return;
        }
        var base = parseAmount();
        if (base <= 0) {
            gstSummary.textContent = '';
            gstSummary.classList.add('d-none');
            return;
        }
        var gst = gstAmount(base);
        var total = Math.round((base + gst) * 100) / 100;
        gstSummary.classList.remove('d-none');
        gstSummary.textContent =
            'GST (' + gstPercent.toFixed(2) + '%): ' + formatMoney(gst) +
            ' · Total: ' + formatMoney(total);
    }

    function setDetailRequired(on) {
        if (methodSelect) {
            methodSelect.required = on;
            methodSelect.disabled = !on;
        }

        statusInputs.forEach(function (input) {
            input.required = on;
            input.disabled = !on;
        });

        if (!on && paidInput) {
            paidInput.required = false;
            paidInput.disabled = true;
            paidInput.value = '';
        }
    }

    function updatePartialField() {
        if (!partialField || !paidInput) {
            return;
        }

        var amount = parseAmount();
        var selected = document.querySelector('.payment-status-input:checked');
        var isPartial = amount > 0 && selected && selected.value === 'partial';

        partialField.classList.toggle('d-none', !isPartial);
        paidInput.required = isPartial;
        paidInput.disabled = !isPartial;
        if (!isPartial) {
            paidInput.value = '';
        }
    }

    function updatePaymentVisibility() {
        var showDetails = parseAmount() > 0;

        detailCols.forEach(function (col) {
            col.classList.toggle('d-none', !showDetails);
        });
        setDetailRequired(showDetails);
        updateGstSummary();

        if (!showDetails) {
            if (partialField) {
                partialField.classList.add('d-none');
            }
            return;
        }

        updatePartialField();
    }

    amountInput.addEventListener('input', updatePaymentVisibility);
    amountInput.addEventListener('change', updatePaymentVisibility);

    statusInputs.forEach(function (input) {
        input.addEventListener('change', updatePartialField);
    });

    updatePaymentVisibility();
})();
