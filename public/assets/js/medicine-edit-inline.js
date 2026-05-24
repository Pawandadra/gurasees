(function () {
    'use strict';

    var rows = document.querySelectorAll('.medicine-inventory-row');
    if (!rows.length) {
        return;
    }

    function rowElements(row) {
        return {
            row: row,
            view: row.querySelector('.medicine-name-view'),
            form: row.querySelector('.medicine-inline-edit-form'),
            input: row.querySelector('.medicine-inline-edit-input'),
            editBtn: row.querySelector('.medicine-edit-trigger'),
        };
    }

    function closeRow(parts, restoreOriginal) {
        if (!parts.view || !parts.form) {
            return;
        }

        parts.view.classList.remove('d-none');
        parts.form.classList.add('d-none');
        parts.row.classList.remove('is-editing');
        if (parts.editBtn) {
            parts.editBtn.classList.remove('d-none');
        }
        if (restoreOriginal && parts.input) {
            var display = parts.row.querySelector('.medicine-name-display');
            if (display) {
                parts.input.value = display.textContent.trim();
            }
        }
    }

    function closeAllExcept(activeRow, restoreOriginal) {
        rows.forEach(function (row) {
            if (row === activeRow) {
                return;
            }
            closeRow(rowElements(row), restoreOriginal);
        });
    }

    function openRow(parts) {
        if (!parts.view || !parts.form || !parts.input) {
            return;
        }

        closeAllExcept(parts.row, true);
        parts.view.classList.add('d-none');
        parts.form.classList.remove('d-none');
        parts.row.classList.add('is-editing');
        if (parts.editBtn) {
            parts.editBtn.classList.add('d-none');
        }
        parts.input.focus();
        parts.input.select();
    }

    rows.forEach(function (row) {
        var parts = rowElements(row);

        if (parts.editBtn) {
            parts.editBtn.addEventListener('click', function () {
                openRow(parts);
            });
        }

        var cancelBtn = row.querySelector('.medicine-inline-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closeRow(parts, true);
            });
        }

        if (parts.input) {
            parts.input.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeRow(parts, true);
                }
            });
        }
    });

    var editingRow = document.querySelector('.medicine-inventory-row.is-editing');
    if (editingRow) {
        var active = rowElements(editingRow);
        if (active.input) {
            active.input.focus();
            active.input.select();
        }
    }
})();
