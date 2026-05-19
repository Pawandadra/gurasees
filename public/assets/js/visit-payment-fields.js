(function () {
    'use strict';

    var section = document.getElementById('visitPaymentSection');
    if (!section) {
        return;
    }

    var methodSelect = document.getElementById('visit_payment_method');
    var statusInputs = section.querySelectorAll('.visit-payment-status-input');
    var partialField = document.getElementById('visitPaymentPartialField');
    var paidInput = document.getElementById('visit_payment_paid_amount');
    var zeroHint = document.getElementById('visitPaymentZeroHint');
    var detailInputs = section.querySelectorAll('.visit-payment-detail-input');
    var requiredMarks = section.querySelectorAll('.visit-payment-required-mark');
    var paymentFields = section.querySelectorAll('.visit-payment-method-col, .visit-payment-status-col');

    function parseGrandTotal() {
        var summary = document.getElementById('summaryGrandTotal');
        if (!summary) {
            return 0;
        }
        var text = (summary.textContent || '').replace(/[^\d.]/g, '');
        var value = parseFloat(text);
        return Number.isFinite(value) ? value : 0;
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

    function updatePartialField(grandTotal) {
        if (!partialField || !paidInput) {
            return;
        }

        var selected = section.querySelector('.visit-payment-status-input:checked');
        var isPartial = grandTotal > 0 && selected && selected.value === 'partial';

        partialField.classList.toggle('d-none', !isPartial);
        paidInput.required = isPartial;
        paidInput.disabled = !isPartial;
        if (!isPartial) {
            paidInput.value = '';
        }
    }

    function updateVisitPaymentVisibility(grandTotal) {
        if (grandTotal === undefined) {
            grandTotal = parseGrandTotal();
        }

        var showDetails = grandTotal > 0;

        paymentFields.forEach(function (col) {
            col.classList.toggle('d-none', !showDetails);
        });
        requiredMarks.forEach(function (mark) {
            mark.classList.toggle('d-none', !showDetails);
        });
        if (zeroHint) {
            zeroHint.classList.toggle('d-none', showDetails);
        }

        setDetailRequired(showDetails);

        if (!showDetails) {
            if (partialField) {
                partialField.classList.add('d-none');
            }
            return;
        }

        updatePartialField(grandTotal);
    }

    statusInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            updatePartialField(parseGrandTotal());
        });
    });

    window.updateVisitPaymentFields = updateVisitPaymentVisibility;

    updateVisitPaymentVisibility();
})();
