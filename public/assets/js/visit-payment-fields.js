(function () {
    'use strict';

    var section = document.getElementById('visitPaymentSection');
    var fieldsGrid = document.getElementById('visitPaymentFields');
    if (!section || !fieldsGrid) {
        return;
    }

    var methodSelect = document.getElementById('visit_payment_method');
    var statusSelect = document.getElementById('visit_payment_status');
    var partialField = document.getElementById('visitPaymentPartialField');
    var paidInput = document.getElementById('visit_payment_paid_amount');
    var requiredMarks = section.querySelectorAll('.visit-payment-required-mark');

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

    function updatePartialField(grandTotal) {
        if (!partialField || !paidInput) {
            return;
        }

        var isPartial = grandTotal > 0 && statusSelect && statusSelect.value === 'partial';

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

        fieldsGrid.classList.toggle('visit-payment-fields--hidden', !showDetails);
        requiredMarks.forEach(function (mark) {
            mark.classList.toggle('d-none', !showDetails);
        });

        setDetailRequired(showDetails);

        if (!showDetails) {
            if (partialField) {
                partialField.classList.add('d-none');
            }
            return;
        }

        updatePartialField(grandTotal);
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            updatePartialField(parseGrandTotal());
        });
    }

    window.updateVisitPaymentFields = updateVisitPaymentVisibility;

    updateVisitPaymentVisibility();
})();
