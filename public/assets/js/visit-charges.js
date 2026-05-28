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
    var gstCourier = parseFloat(root.getAttribute('data-gst-courier') || '0') || 0;

    var visitChargeInput = document.getElementById('visit_charge');
    var medicineTotalInput = document.getElementById('medicine_total');
    var courierChargeInput = document.getElementById('courier_charge');
    var searchInput = document.getElementById('visitMedicineSearchInput');
    var searchResults = document.getElementById('visitMedicineSearchResults');
    var cartEl = document.getElementById('visitMedicineCart');
    var cartTable = document.getElementById('visitMedicineTable');
    var courierToggleAll = document.getElementById('visitCourierToggleAll');
    var cartWrap = document.querySelector('.visit-medicine-cart-wrap');
    var cartEmptyEl = document.getElementById('visitMedicineCartEmpty');
    var hiddenEl = document.getElementById('visitMedicineHiddenInputs');

    var summaryVisitCharge = document.getElementById('summaryVisitCharge');
    var summaryVisitGst = document.getElementById('summaryVisitGst');
    var summaryVisitBase = document.getElementById('summaryVisitBase');
    var summaryMedicineGst = document.getElementById('summaryMedicineGst');
    var summaryMedicineBase = document.getElementById('summaryMedicineBase');
    var summaryCourierGst = document.getElementById('summaryCourierGst');
    var summaryCourierBase = document.getElementById('summaryCourierBase');
    var summaryGrandTotal = document.getElementById('summaryGrandTotal');
    var courierSummaryRows = root.querySelectorAll('.visit-billing-courier-row');

    var cart = {};
    var activeIndex = -1;

    function cartLine(id) {
        var entry = cart[String(id)];
        if (entry && typeof entry === 'object') {
            return entry;
        }
        var qty = parseInt(entry, 10);
        return { qty: Number.isFinite(qty) && qty > 0 ? qty : 0, courier: false };
    }

    function cartQty(id) {
        return cartLine(id).qty;
    }

    function cartCourier(id) {
        return cartLine(id).courier;
    }

    function setCartLine(id, qty, courier) {
        if (qty < 1) {
            delete cart[String(id)];
            return;
        }
        cart[String(id)] = { qty: qty, courier: !!courier };
    }

    function formatMoney(value) {
        return currency + Number(value).toFixed(2);
    }

    var splitFn =
        typeof window.gstSplitInclusive === 'function'
            ? window.gstSplitInclusive
            : function (net, percent) {
                  return { base: net, gst: 0, total: net };
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

    function parseVisitCharge() {
        if (!visitChargeInput) {
            return 0;
        }
        var value = parseFloat(visitChargeInput.value);
        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function medicineSubtotal() {
        if (!medicineTotalInput) {
            return 0;
        }
        var value = parseFloat(medicineTotalInput.value);
        if (!Number.isFinite(value) || value < 0) {
            return 0;
        }
        return Math.round(value * 100) / 100;
    }

    function hasCourierItems() {
        return Object.keys(cart).some(function (id) {
            return cartCourier(id);
        });
    }

    function courierSubtotal() {
        if (!hasCourierItems()) {
            return 0;
        }
        if (!courierChargeInput) {
            return 0;
        }
        var value = parseFloat(courierChargeInput.value);
        if (!Number.isFinite(value) || value < 0) {
            return 0;
        }
        return Math.round(value * 100) / 100;
    }

    function updateSummary() {
        var visitNet = parseVisitCharge();
        var visitSplit = splitFn(visitNet, gstVisit);
        var medNet = medicineSubtotal();
        var medSplit = splitFn(medNet, gstMedicine);
        var courierNet = courierSubtotal();
        var courierSplit = splitFn(courierNet, gstCourier);
        var grand = Math.round(
            (visitSplit.total + medSplit.total + courierSplit.total) * 100
        ) / 100;

        if (summaryVisitCharge) {
            summaryVisitCharge.textContent = formatMoney(visitNet);
        }
        if (summaryVisitGst) {
            summaryVisitGst.textContent = formatMoney(visitSplit.gst);
        }
        if (summaryVisitBase) {
            summaryVisitBase.textContent = formatMoney(visitSplit.base);
        }
        if (summaryMedicineGst) {
            summaryMedicineGst.textContent = formatMoney(medSplit.gst);
        }
        if (summaryMedicineBase) {
            summaryMedicineBase.textContent = formatMoney(medSplit.base);
        }
        courierSummaryRows.forEach(function (row) {
            row.classList.toggle('d-none', !hasCourierItems());
        });
        if (summaryCourierGst) {
            summaryCourierGst.textContent = formatMoney(courierSplit.gst);
        }
        if (summaryCourierBase) {
            summaryCourierBase.textContent = formatMoney(courierSplit.base);
        }
        if (summaryGrandTotal) {
            summaryGrandTotal.textContent = formatMoney(grand);
        }

        if (typeof window.updateVisitPaymentFields === 'function') {
            window.updateVisitPaymentFields(grand);
        }
    }

    function syncHiddenInputs() {
        if (!hiddenEl) {
            return;
        }
        hiddenEl.innerHTML = '';
        Object.keys(cart).forEach(function (id) {
            var qty = cartQty(id);
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
            var courierInput = document.createElement('input');
            courierInput.type = 'hidden';
            courierInput.name = 'medicine_courier[]';
            courierInput.value = cartCourier(id) ? '1' : '0';
            hiddenEl.appendChild(idInput);
            hiddenEl.appendChild(qtyInput);
            hiddenEl.appendChild(courierInput);
        });
    }

    function renderCart() {
        if (!cartEl) {
            return;
        }

        if (!hasCourierItems() && courierChargeInput) {
            courierChargeInput.value = '';
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

            var qty = cartQty(id);
            var forCourier = cartCourier(id);
            var row = document.createElement('tr');
            row.className = 'visit-medicine-cart-row';
            row.innerHTML =
                '<td><span class="fw-medium">' + escapeHtml(medicineDisplayName(med)) + '</span></td>' +
                '<td class="text-center"><label class="visually-hidden">' + escapeHtml(root.getAttribute('data-label-qty') || 'Qty') + '</label>' +
                '<input type="number" class="form-control form-control-sm visit-cart-qty mx-auto" min="1" step="1" value="' + qty + '" data-id="' + id + '"></td>' +
                '<td class="text-center visit-cart-courier-cell">' +
                '<label class="visit-cart-courier-label mb-0" title="' + escapeHtml(root.getAttribute('data-label-courier') || 'Courier') + '">' +
                '<input type="checkbox" class="form-check-input visit-cart-courier" data-id="' + id + '"' + (forCourier ? ' checked' : '') + '>' +
                '<span class="visually-hidden">' + escapeHtml(root.getAttribute('data-label-courier') || 'Courier') + '</span>' +
                '</label></td>' +
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
                setCartLine(medId, val, cartCourier(medId));
                renderCart();
                syncHiddenInputs();
                updateSummary();
            });
        });

        cartEl.querySelectorAll('.visit-cart-courier').forEach(function (input) {
            input.addEventListener('change', function () {
                var medId = input.getAttribute('data-id');
                if (!medId) {
                    return;
                }
                setCartLine(medId, cartQty(medId), input.checked);
                renderCart();
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

    function toggleAllCourier() {
        if (!cartEl) {
            return;
        }

        var boxes = cartEl.querySelectorAll('.visit-cart-courier');
        if (!boxes || boxes.length === 0) {
            return;
        }

        var allChecked = true;
        boxes.forEach(function (b) {
            if (!b.checked) {
                allChecked = false;
            }
        });

        var next = !allChecked;
        boxes.forEach(function (b) {
            b.checked = next;
            var medId = b.getAttribute('data-id');
            if (medId) {
                setCartLine(medId, cartQty(medId), next);
            }
        });

        renderCart();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function medicineDisplayName(med) {
        var name = med && med.name != null ? String(med.name) : '';
        // We are not managing inventory yet; keep labels as medicine name only.
        // If any older data/logic appended a missing number it can show up as "+NaN" in the UI.
        name = name.replace(/\s*\+\s*NaN\s*$/i, '').trim();
        return name;
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
        setCartLine(key, cartQty(key) + 1, cartCourier(key));
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
            btn.innerHTML =
                '<span class="visit-medicine-search-name">' +
                escapeHtml(medicineDisplayName(item)) +
                '</span>';
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
        var searchWrap = document.getElementById('visitMedicineSearchWrap');

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
            } else if (event.key === 'Enter') {
                event.preventDefault();
                var pickIndex = activeIndex >= 0 ? activeIndex : 0;
                items[pickIndex].click();
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

        if (searchWrap) {
            searchWrap.addEventListener('search-dropdown:close', hideSearchResults);
        }
    }

    if (visitChargeInput) {
        visitChargeInput.addEventListener('input', updateSummary);
        visitChargeInput.addEventListener('change', updateSummary);
    }

    if (medicineTotalInput) {
        medicineTotalInput.addEventListener('input', updateSummary);
        medicineTotalInput.addEventListener('change', updateSummary);
    }

    if (courierChargeInput) {
        courierChargeInput.addEventListener('input', updateSummary);
        courierChargeInput.addEventListener('change', updateSummary);
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
        var courierQty = parseInt(line.courier_quantity, 10);
        if (id && qty > 0) {
            setCartLine(id, qty, courierQty > 0);
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

    if (courierToggleAll) {
        courierToggleAll.addEventListener('click', function () {
            toggleAllCourier();
        });
        courierToggleAll.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleAllCourier();
            }
        });
    }
})();
