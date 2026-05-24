(function () {
    'use strict';

    var form = document.getElementById('medicineAddForm');
    if (!form) {
        return;
    }

    var trigger = form.querySelector('.confirm-action-trigger');
    var nameInput = document.getElementById('medicine_name');
    var confirmTemplate = form.getAttribute('data-confirm-message') || '';

    if (!trigger || !nameInput) {
        return;
    }

    trigger.addEventListener('click', function (event) {
        if (!form.reportValidity()) {
            event.stopImmediatePropagation();
            return;
        }

        var name = nameInput.value.trim();
        if (confirmTemplate && name !== '') {
            trigger.setAttribute('data-confirm', confirmTemplate.replace(':name', name));
        }
    }, true);
})();
