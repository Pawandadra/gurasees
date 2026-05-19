(function () {
    'use strict';

    var root = document.getElementById('visitBilling');
    if (!root) {
        return;
    }

    var catalog = [];
    try {
        catalog = JSON.parse(root.getAttribute('data-medicines') || '[]');
    } catch (e) {
        catalog = [];
    }

    var currency = root.getAttribute('data-currency') || '₹';
    var gstVisit = parseFloat(root.getAttribute('data-gst-visit') || '0') || 0;
    var gstMedicine = parseFloat(root.getAttribute('data-gst-medicine') || '0') || 0;

    var visitChargeInput = document.getElementById('visit_charge');
    var searchInput = document.getElementById('visitMedicineSearchInput');
    var searchResults = document.getElementById('visitMedicineSearchResults');
    var cartEl = document.getElementById('visitMedicineCart');
    var cartTable = document.getElementById('visitMedicineTable');
    var cartWrap = document.querySelector('.visit-medicine-cart-wrap');
    var cartEmptyEl = document.getElementById('visitMedicineCartEmpty');
    var hiddenEl = document.getElementById('visitMedicineHiddenInputs');

    var summaryVisitCharge = document.getElementById('summaryVisitCharge');
    var summaryVisitGst = document.getElementById('summaryVisitGst');
    var summaryMedicineSubtotal = document.getElementById('summaryMedicineSubtotal');
    var summaryMedicineGst = document.getElementById('summaryMedicineGst');
    var summaryGrandTotal = document.getElementById('summaryGrandTotal');

    var cart = {};
    var activeIndex = -1;

    function formatMoney(value) {
        return currency + Number(value).toFixed(2);
    }

    function gstAmount(base, percent) {
        if (base <= 0 || percent <= 0) {
            return 0;
        }
        return Math.round(base * percent * 100) / 10000;
    }

    function medicineById(id) {
        var key = String(id);
        for (var i = 0; i < catalog.length; i++) {
            if (String(catalog[i].id) === key) {
                return catalog[i];
            }
        }
        return null;
    }

    function parseVisitCharge() {
        if (!visitChargeInput) {
            return 0;
        }
        var value = parseFloat(visitChargeInput.value);
        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function medicineSubtotal() {
        var total = 0;
        Object.keys(cart).forEach(function (id) {
            var med = medicineById(id);
            var qty = cart[id];
            if (med && qty > 0) {
                total += parseFloat(med.unit_price) * qty;
            }
        });
        return Math.round(total * 100) / 100;
    }

    function updateSummary() {
        var visitCharge = parseVisitCharge();
        var visitGst = gstAmount(visitCharge, gstVisit);
        var medSubtotal = medicineSubtotal();
        var medGst = gstAmount(medSubtotal, gstMedicine);
        var grand = Math.round((visitCharge + visitGst + medSubtotal + medGst) * 100) / 100;

        if (summaryVisitCharge) {
            summaryVisitCharge.textContent = formatMoney(visitCharge);
        }
        if (summaryVisitGst) {
            summaryVisitGst.textContent = formatMoney(visitGst);
        }
        if (summaryMedicineSubtotal) {
            summaryMedicineSubtotal.textContent = formatMoney(medSubtotal);
        }
        if (summaryMedicineGst) {
            summaryMedicineGst.textContent = formatMoney(medGst);
        }
        if (summaryGrandTotal) {
            summaryGrandTotal.textContent = formatMoney(grand);
        }
    }

    function syncHiddenInputs() {
        if (!hiddenEl) {
            return;
        }
        hiddenEl.innerHTML = '';
        Object.keys(cart).forEach(function (id) {
            var qty = cart[id];
            if (qty < 1) {
                return;
            }
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'medicine_id[]';
            idInput.value = id;
            var qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = 'medicine_qty[]';
            qtyInput.value = String(qty);
            hiddenEl.appendChild(idInput);
            hiddenEl.appendChild(qtyInput);
        });
    }

    function renderCart() {
        if (!cartEl) {
            return;
        }

        cartEl.innerHTML = '';

        var ids = Object.keys(cart);
        var hasItems = ids.length > 0;
        if (cartEmptyEl) {
            cartEmptyEl.classList.toggle('d-none', hasItems);
        }
        if (cartTable) {
            cartTable.classList.toggle('d-none', !hasItems);
        }

        ids.sort(function (a, b) {
            var ma = medicineById(a);
            var mb = medicineById(b);
            return (ma ? ma.name : '').localeCompare(mb ? mb.name : '');
        });

        ids.forEach(function (id) {
            var med = medicineById(id);
            if (!med) {
                delete cart[id];
                return;
            }

            var qty = cart[id];
            var row = document.createElement('tr');
            row.className = 'visit-medicine-cart-row';
            row.innerHTML =
                '<td><span class="fw-medium">' + escapeHtml(med.name) + '</span></td>' +
                '<td class="text-center"><label class="visually-hidden">' + escapeHtml(root.getAttribute('data-label-qty') || 'Qty') + '</label>' +
                '<input type="number" class="form-control form-control-sm visit-cart-qty mx-auto" min="1" step="1" value="' + qty + '" data-id="' + id + '"></td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger visit-cart-remove px-2" data-id="' + id + '" title="' +
                escapeHtml(root.getAttribute('data-label-remove') || 'Remove') + '">×</button></td>';

            cartEl.appendChild(row);
        });

        cartEl.querySelectorAll('.visit-cart-qty').forEach(function (input) {
            input.addEventListener('input', function () {
                var medId = input.getAttribute('data-id');
                var val = parseInt(input.value, 10);
                if (!medId || !Number.isFinite(val) || val < 1) {
                    return;
                }
                cart[medId] = val;
                renderCart();
                syncHiddenInputs();
                updateSummary();
            });
        });

        cartEl.querySelectorAll('.visit-cart-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var medId = btn.getAttribute('data-id');
                if (medId) {
                    delete cart[medId];
                }
                renderCart();
                syncHiddenInputs();
                updateSummary();
            });
        });

        syncHiddenInputs();
        updateSummary();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function hideSearchResults() {
        if (!searchResults) {
            return;
        }
        searchResults.hidden = true;
        searchResults.innerHTML = '';
        activeIndex = -1;
        if (searchInput) {
            searchInput.setAttribute('aria-expanded', 'false');
        }
    }

    function showSearchResults() {
        if (!searchResults) {
            return;
        }
        searchResults.hidden = false;
        if (searchInput) {
            searchInput.setAttribute('aria-expanded', 'true');
        }
    }

    function filterMedicines(query) {
        query = query.trim().toLowerCase();
        if (query === '') {
            return catalog.slice();
        }
        return catalog.filter(function (item) {
            return item.name.toLowerCase().indexOf(query) !== -1;
        });
    }

    function addMedicine(id) {
        var key = String(id);
        cart[key] = (cart[key] || 0) + 1;
        renderCart();
        if (searchInput) {
            searchInput.value = '';
        }
        hideSearchResults();
    }

    function renderSearchResults(results) {
        if (!searchResults) {
            return;
        }
        searchResults.innerHTML = '';
        activeIndex = -1;

        if (results.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'visit-medicine-search-empty';
            empty.textContent = root.getAttribute('data-label-empty') || 'No medicines found';
            searchResults.appendChild(empty);
            showSearchResults();
            return;
        }

        results.forEach(function (item, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'visit-medicine-search-item';
            btn.setAttribute('role', 'option');
            btn.setAttribute('data-id', String(item.id));
            btn.innerHTML = '<span class="visit-medicine-search-name">' + escapeHtml(item.name) + '</span>';
            btn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            btn.addEventListener('click', function () {
                addMedicine(item.id);
            });
            searchResults.appendChild(btn);
        });

        showSearchResults();
    }

    if (searchInput) {
        function showFilteredResults() {
            renderSearchResults(filterMedicines(searchInput.value));
        }

        searchInput.addEventListener('input', showFilteredResults);
        searchInput.addEventListener('focus', showFilteredResults);
        searchInput.addEventListener('click', showFilteredResults);

        searchInput.addEventListener('keydown', function (event) {
            if (!searchResults || searchResults.hidden) {
                return;
            }
            var items = searchResults.querySelectorAll('.visit-medicine-search-item');
            if (items.length === 0) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                items[activeIndex].click();
                return;
            } else if (event.key === 'Escape') {
                hideSearchResults();
                return;
            } else {
                return;
            }
            items.forEach(function (el, i) {
                el.classList.toggle('active', i === activeIndex);
            });
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                hideSearchResults();
            }
        });
    }

    if (visitChargeInput) {
        visitChargeInput.addEventListener('input', updateSummary);
        visitChargeInput.addEventListener('change', updateSummary);
    }

    var initial = [];
    if (cartWrap) {
        try {
            initial = JSON.parse(cartWrap.getAttribute('data-initial') || '[]');
        } catch (e) {
            initial = [];
        }
    }
    initial.forEach(function (line) {
        var id = String(line.medicine_id);
        var qty = parseInt(line.quantity, 10);
        if (id && qty > 0) {
            cart[id] = qty;
        }
    });

    renderCart();
    updateSummary();

    var form = root.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            syncHiddenInputs();
        });
    }
})();
