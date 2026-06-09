(function () {
    'use strict';

    var list = document.getElementById('stockItemList');
    var addBtn = document.getElementById('stockAddItemBtn');
    var totalEl = document.getElementById('stockBillTotal');
    if (!list || !addBtn) {
        return;
    }

    var removeLabel = addBtn.getAttribute('data-remove-label') || 'Remove';
    var namePlaceholder = addBtn.getAttribute('data-name-placeholder') || '';

    function parseAmount(input) {
        var value = parseFloat(input && input.value ? input.value : '');
        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function formatTotal(amount) {
        return typeof window.moneyFormatDisplay === 'function'
            ? window.moneyFormatDisplay(amount)
            : String(amount);
    }

    function updateTotal() {
        if (!totalEl) {
            return;
        }
        var sum = 0;
        list.querySelectorAll('.stock-item-amount').forEach(function (input) {
            sum += parseAmount(input);
        });
        totalEl.textContent = formatTotal(sum);
    }

    function bindRow(row) {
        var amountInput = row.querySelector('.stock-item-amount');
        if (amountInput && amountInput.dataset.bound !== '1') {
            amountInput.dataset.bound = '1';
            amountInput.addEventListener('input', updateTotal);
            amountInput.addEventListener('change', updateTotal);
        }

        var btn = row.querySelector('.stock-remove-item');
        if (!btn || btn.dataset.bound === '1') {
            return;
        }
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
            var rows = list.querySelectorAll('.stock-item-row');
            if (rows.length <= 1) {
                row.querySelectorAll('input').forEach(function (input) {
                    if (input.name === 'item_quantities[]') {
                        input.value = '1';
                    } else {
                        input.value = '';
                    }
                });
                updateTotal();
                return;
            }
            row.remove();
            updateRemoveButtons();
            updateTotal();
        });
    }

    function updateRemoveButtons() {
        var rows = list.querySelectorAll('.stock-item-row');
        rows.forEach(function (row) {
            var btn = row.querySelector('.stock-remove-item');
            if (btn) {
                btn.disabled = rows.length <= 1;
            }
        });
    }

    function addRow(name, quantity, amount) {
        var row = document.createElement('div');
        row.className = 'stock-item-row row g-2 mb-2 align-items-center';
        row.innerHTML =
            '<div class="col-md-5">' +
            '<input type="text" class="form-control" name="item_names[]" maxlength="255" ' +
            'placeholder="' +
            namePlaceholder.replace(/"/g, '&quot;') +
            '" value="' +
            (name || '').replace(/"/g, '&quot;') +
            '">' +
            '</div>' +
            '<div class="col-md-2">' +
            '<input type="number" class="form-control" name="item_quantities[]" ' +
            'min="1" step="1" value="' +
            (quantity || '1').replace(/"/g, '&quot;') +
            '">' +
            '</div>' +
       
            '<div class="col-md-4">' +
            '<input type="number" class="form-control stock-item-amount" name="item_amounts[]" ' +
            'min="0.01" step="0.01" placeholder="0" value="' +
            (amount || '').replace(/"/g, '&quot;') +
            '">' +
            '</div>' +
            '<div class="col-md-1 text-end">' +
            '<button type="button" class="btn btn-outline-secondary stock-remove-item w-100" ' +
            'title="' +
            removeLabel.replace(/"/g, '&quot;') +
            '">×</button>' +
            '</div>';
        list.appendChild(row);
        bindRow(row);
        updateRemoveButtons();
        updateTotal();
    }

    list.querySelectorAll('.stock-item-row').forEach(bindRow);
    updateRemoveButtons();
    updateTotal();

    addBtn.addEventListener('click', function () {
        addRow('', '1', '');
        var inputs = list.querySelectorAll('input[name="item_names[]"]');
        var last = inputs[inputs.length - 1];
        if (last) {
            last.focus();
        }
    });
})();
