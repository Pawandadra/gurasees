(function () {
    'use strict';

    var root = document.getElementById('visitMedicines');
    if (!root) {
        return;
    }

    var catalog = [];
    try {
        catalog = JSON.parse(root.getAttribute('data-medicines') || '[]');
    } catch (e) {
        catalog = [];
    }

    if (catalog.length === 0) {
        return;
    }

    var linesEl = document.getElementById('visitMedicineLines');
    var hiddenEl = document.getElementById('visitMedicineHiddenInputs');
    var totalEl = document.getElementById('visitMedicineTotal');
    var addBtn = document.getElementById('visitMedicineAddBtn');
    var currency = root.getAttribute('data-currency') || '₹';
    var labelTotal = root.getAttribute('data-label-total') || 'Total';

    var labels = {
        medicine: root.getAttribute('data-label-medicine') || 'Medicine',
        quantity: root.getAttribute('data-label-quantity') || 'Qty',
        price: root.getAttribute('data-label-price') || 'Price',
        line: root.getAttribute('data-label-line') || 'Line total',
        select: root.getAttribute('data-label-select') || 'Select',
        remove: root.getAttribute('data-label-remove') || 'Remove'
    };

    function medicineById(id) {
        var key = String(id);
        for (var i = 0; i < catalog.length; i++) {
            if (String(catalog[i].id) === key) {
                return catalog[i];
            }
        }
        return null;
    }

    function formatMoney(value) {
        return currency + Number(value).toFixed(2);
    }

    function buildSelect(selectedId) {
        var select = document.createElement('select');
        select.className = 'form-select form-select-sm visit-medicine-select';
        select.setAttribute('aria-label', labels.medicine);

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = labels.select;
        select.appendChild(placeholder);

        catalog.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.name + ' (' + formatMoney(parseFloat(item.unit_price)) + ')';
            if (String(item.id) === String(selectedId)) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        return select;
    }

    function createLine(initial) {
        var row = document.createElement('div');
        row.className = 'visit-medicine-line row g-2 align-items-end mb-2';

        var medCol = document.createElement('div');
        medCol.className = 'col-md-5 col-lg-4';
        var medLabel = document.createElement('label');
        medLabel.className = 'form-label small mb-1';
        medLabel.textContent = labels.medicine;
        medCol.appendChild(medLabel);
        medCol.appendChild(buildSelect(initial && initial.medicine_id ? initial.medicine_id : ''));

        var qtyCol = document.createElement('div');
        qtyCol.className = 'col-4 col-md-2 col-lg-2';
        var qtyLabel = document.createElement('label');
        qtyLabel.className = 'form-label small mb-1';
        qtyLabel.textContent = labels.quantity;
        var qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.min = '1';
        qtyInput.step = '1';
        qtyInput.className = 'form-control form-control-sm visit-medicine-qty';
        qtyInput.value = initial && initial.quantity ? String(initial.quantity) : '1';
        qtyCol.appendChild(qtyLabel);
        qtyCol.appendChild(qtyInput);

        var lineCol = document.createElement('div');
        lineCol.className = 'col-5 col-md-3 col-lg-3';
        var lineLabel = document.createElement('label');
        lineLabel.className = 'form-label small mb-1';
        lineLabel.textContent = labels.line;
        var lineValue = document.createElement('p');
        lineValue.className = 'mb-0 small fw-semibold visit-medicine-line-total';
        lineValue.textContent = formatMoney(0);
        lineCol.appendChild(lineLabel);
        lineCol.appendChild(lineValue);

        var removeCol = document.createElement('div');
        removeCol.className = 'col-3 col-md-2 col-lg-auto';
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger w-100 visit-medicine-remove';
        removeBtn.textContent = labels.remove;
        removeCol.appendChild(removeBtn);

        row.appendChild(medCol);
        row.appendChild(qtyCol);
        row.appendChild(lineCol);
        row.appendChild(removeCol);

        function refreshLine() {
            var med = medicineById(row.querySelector('.visit-medicine-select').value);
            var qty = parseInt(row.querySelector('.visit-medicine-qty').value, 10);
            if (!med || !Number.isFinite(qty) || qty < 1) {
                lineValue.textContent = formatMoney(0);
            } else {
                lineValue.textContent = formatMoney(parseFloat(med.unit_price) * qty);
            }
            syncHiddenInputs();
            updateGrandTotal();
        }

        row.querySelector('.visit-medicine-select').addEventListener('change', refreshLine);
        row.querySelector('.visit-medicine-qty').addEventListener('input', refreshLine);
        removeBtn.addEventListener('click', function () {
            row.remove();
            syncHiddenInputs();
            updateGrandTotal();
        });

        refreshLine();
        return row;
    }

    function syncHiddenInputs() {
        if (!hiddenEl) {
            return;
        }
        hiddenEl.innerHTML = '';
        var rows = linesEl.querySelectorAll('.visit-medicine-line');
        rows.forEach(function (row) {
            var medId = row.querySelector('.visit-medicine-select').value;
            var qty = parseInt(row.querySelector('.visit-medicine-qty').value, 10);
            if (!medId || !Number.isFinite(qty) || qty < 1) {
                return;
            }
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'medicine_id[]';
            idInput.value = medId;
            var qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = 'medicine_qty[]';
            qtyInput.value = String(qty);
            hiddenEl.appendChild(idInput);
            hiddenEl.appendChild(qtyInput);
        });
    }

    function updateGrandTotal() {
        var total = 0;
        var rows = linesEl.querySelectorAll('.visit-medicine-line');
        rows.forEach(function (row) {
            var med = medicineById(row.querySelector('.visit-medicine-select').value);
            var qty = parseInt(row.querySelector('.visit-medicine-qty').value, 10);
            if (med && Number.isFinite(qty) && qty > 0) {
                total += parseFloat(med.unit_price) * qty;
            }
        });
        if (totalEl) {
            totalEl.textContent = labelTotal + ': ' + formatMoney(total);
        }
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            linesEl.appendChild(createLine(null));
        });
    }

    var initial = [];
    try {
        initial = JSON.parse(linesEl.getAttribute('data-initial') || '[]');
    } catch (e) {
        initial = [];
    }

    if (initial.length > 0) {
        initial.forEach(function (line) {
            linesEl.appendChild(createLine(line));
        });
    }

    var form = root.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            syncHiddenInputs();
        });
    }

    updateGrandTotal();
})();
