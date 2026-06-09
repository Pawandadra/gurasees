(function () {
    'use strict';

    var section = document.getElementById('patientPaymentSection');
    var amountInput = document.getElementById('payment_amount');
    var detailCols = section ? section.querySelectorAll('.payment-detail-col') : [];
    var partialField = document.getElementById('paymentPartialField');
    var paidInput = document.getElementById('payment_paid_amount');
    var methodSelect = document.getElementById('payment_method');
    var statusSelect = document.getElementById('payment_status');
    var gstSummary = document.getElementById('paymentGstSummary');

    if (!amountInput || !detailCols.length) {
        return;
    }

    var gstPercent = 0;
    if (section) {
        gstPercent = parseFloat(section.getAttribute('data-gst-percent') || '0') || 0;
    }

    var splitFn =
        typeof window.gstSplitInclusive === 'function'
            ? window.gstSplitInclusive
            : function (net, percent) {
                  return { base: net, gst: 0, total: net };
              };

    function parseAmount() {
        var value = parseFloat(amountInput.value);
        return Number.isFinite(value) ? value : 0;
    }

    function formatMoney(value) {
        return typeof window.moneyFormatAmount === 'function'
            ? window.moneyFormatAmount(value)
            : String(Number(value).toFixed(2)).replace(/\.00$/, '');
    }

    function updateGstSummary() {
        if (!gstSummary) {
            return;
        }
        var net = parseAmount();
        if (net <= 0) {
            gstSummary.textContent = '';
            gstSummary.classList.add('d-none');
            return;
        }
        var split = splitFn(net, gstPercent);
        gstSummary.classList.remove('d-none');
        gstSummary.textContent =
            (gstSummary.getAttribute('data-label-gst') || 'GST') +
            ' (' +
            gstPercent.toFixed(2) +
            '%): ' +
            formatMoney(split.gst) +
            ' · ' +
            (gstSummary.getAttribute('data-label-without-gst') || 'Without GST') +
            ': ' +
            formatMoney(split.base);
    }

    function setDetailRequired(on) {
        if (methodSelect) {
            methodSelect.required = on;
            methodSelect.disabled = !on;
        }

        if (statusSelect) {
            statusSelect.required = on;
            statusSelect.disabled = !on;
        }

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

        var net = parseAmount();
        var isPartial = net > 0 && statusSelect && statusSelect.value === 'partial';

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

    if (statusSelect) {
        statusSelect.addEventListener('change', updatePartialField);
    }

    updatePaymentVisibility();
})();
