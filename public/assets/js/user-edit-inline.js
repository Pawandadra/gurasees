(function () {
    'use strict';

    var pairs = [];
    document.querySelectorAll('.user-manage-row').forEach(function (row) {
        var editRow = row.nextElementSibling;
        if (editRow && editRow.classList.contains('user-edit-row')) {
            pairs.push({ main: row, edit: editRow });
        }
    });

    if (!pairs.length) {
        return;
    }

    function closePair(pair, restore) {
        pair.edit.classList.add('d-none');
        pair.main.classList.remove('is-editing');

        var editBtn = pair.main.querySelector('.user-edit-trigger');
        if (editBtn) {
            editBtn.classList.remove('d-none');
        }

        var form = pair.edit.querySelector('.user-inline-edit-form');
        if (!form) {
            return;
        }

        if (restore) {
            var nameDisplay = pair.main.querySelector('.user-name-display');
            var nameInput = form.querySelector('input[name="name"]');
            if (nameDisplay && nameInput) {
                nameInput.value = nameDisplay.textContent.trim();
            }
            var statusBadge = pair.main.querySelector('.user-status-badge');
            var statusSelect = form.querySelector('select[name="is_active"]');
            if (statusBadge && statusSelect && !statusSelect.disabled) {
                statusSelect.value = statusBadge.classList.contains('user-status-active') ? '1' : '0';
            }
            form.querySelectorAll('input[type="password"]').forEach(function (input) {
                input.value = '';
            });
        }
    }

    function closeAllExcept(activePair, restore) {
        pairs.forEach(function (pair) {
            if (pair !== activePair) {
                closePair(pair, restore);
            }
        });
    }

    function openPair(pair) {
        closeAllExcept(pair, true);
        pair.edit.classList.remove('d-none');
        pair.main.classList.add('is-editing');

        var editBtn = pair.main.querySelector('.user-edit-trigger');
        if (editBtn) {
            editBtn.classList.add('d-none');
        }

        var nameInput = pair.edit.querySelector('input[name="name"]');
        if (nameInput) {
            nameInput.focus();
            nameInput.select();
        }
    }

    pairs.forEach(function (pair) {
        var editBtn = pair.main.querySelector('.user-edit-trigger');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                openPair(pair);
            });
        }

        var cancelBtn = pair.edit.querySelector('.user-inline-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closePair(pair, true);
            });
        }

        var nameInput = pair.edit.querySelector('input[name="name"]');
        if (nameInput) {
            nameInput.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closePair(pair, true);
                }
            });
        }
    });

    pairs.forEach(function (pair) {
        if (!pair.edit.classList.contains('d-none')) {
            var nameInput = pair.edit.querySelector('input[name="name"]');
            if (nameInput) {
                nameInput.focus();
                nameInput.select();
            }
        }
    });
})();
