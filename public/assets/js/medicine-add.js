(function () {
    'use strict';

    var form = document.getElementById('medicineAddForm');
    if (!form) {
        return;
    }

    var typeSelect = document.getElementById('medicine_type');
    var unitFields = form.querySelectorAll('[data-medicine-fields="unit"]');
    var bulkFields = form.querySelectorAll('[data-medicine-fields="bulk"]');

    function setFieldRequired(el, required) {
        if (!el || el.type === 'hidden') {
            return;
        }
        if (required) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
        }
    }

    function toggleType() {
        var isBulk = typeSelect && typeSelect.value === 'bulk';
        unitFields.forEach(function (wrap) {
            wrap.hidden = isBulk;
            wrap.querySelectorAll('input, select, textarea').forEach(function (el) {
                setFieldRequired(el, !isBulk);
            });
        });
        bulkFields.forEach(function (wrap) {
            wrap.hidden = !isBulk;
            wrap.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.hasAttribute('data-volume-unit-field')) {
                    return;
                }
                setFieldRequired(el, isBulk);
            });
        });
    }

    function formatLitreValue(ml) {
        var litres = ml / 1000;
        if (litres >= 1 && Math.abs(litres - Math.round(litres)) < 0.0001) {
            return String(Math.round(litres));
        }
        return litres.toFixed(3).replace(/\.?0+$/, '');
    }

    function initVolumeGroup(group) {
        var input = group.querySelector('[data-volume-input]');
        var unitField = group.querySelector('[data-volume-unit-field]');
        var toggle = group.querySelector('.volume-unit-toggle');
        if (!input || !toggle) {
            return;
        }

        var unit = (unitField && unitField.value === 'l') ? 'l' : 'ml';
        toggle.setAttribute('data-unit', unit);
        toggle.textContent = unit === 'l' ? 'L' : 'ml';

        if (unit === 'l' && input.value !== '') {
            var ml = parseFloat(input.value);
            if (Number.isFinite(ml)) {
                input.value = formatLitreValue(ml);
                input.min = '0.001';
                input.step = '0.001';
            }
        }

        toggle.addEventListener('click', function () {
            var current = parseFloat(input.value);
            if (!Number.isFinite(current)) {
                current = 0;
            }

            if (toggle.getAttribute('data-unit') === 'ml') {
                toggle.setAttribute('data-unit', 'l');
                toggle.textContent = 'L';
                if (unitField) {
                    unitField.value = 'l';
                }
                input.value = current > 0 ? formatLitreValue(current) : '';
                input.min = '0.001';
                input.step = '0.001';
            } else {
                toggle.setAttribute('data-unit', 'ml');
                toggle.textContent = 'ml';
                if (unitField) {
                    unitField.value = 'ml';
                }
                input.value = current > 0 ? String(Math.round(current * 1000)) : '';
                input.min = '1';
                input.step = '1';
            }
        });
    }

    function normalizeVolumeForSubmit(group) {
        var input = group.querySelector('[data-volume-input]');
        var toggle = group.querySelector('.volume-unit-toggle');
        var unitField = group.querySelector('[data-volume-unit-field]');
        if (!input || !toggle) {
            return;
        }

        var val = parseFloat(input.value);
        if (!Number.isFinite(val) || val <= 0) {
            return;
        }

        if (toggle.getAttribute('data-unit') === 'l') {
            input.value = String(Math.max(1, Math.round(val * 1000)));
            if (unitField) {
                unitField.value = 'l';
            }
        } else if (unitField) {
            unitField.value = 'ml';
        }
    }

    form.querySelectorAll('[data-volume-group]').forEach(initVolumeGroup);

    form.addEventListener('submit', function () {
        form.querySelectorAll('[data-volume-group]').forEach(normalizeVolumeForSubmit);
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleType);
        toggleType();
    }
})();
