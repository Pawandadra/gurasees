(function () {
    'use strict';

    var form = document.getElementById('userAddForm');
    if (!form) {
        return;
    }

    var trigger = form.querySelector('.confirm-action-trigger');
    var nameInput = document.getElementById('user_name');
    var confirmTemplate = form.getAttribute('data-confirm-message') || '';

    if (!trigger) {
        return;
    }

    trigger.addEventListener('click', function (event) {
        if (!form.reportValidity()) {
            event.stopImmediatePropagation();
            return;
        }

        var name = nameInput ? nameInput.value.trim() : '';
        if (confirmTemplate && name !== '') {
            trigger.setAttribute('data-confirm', confirmTemplate.replace(':name', name));
        }
    }, true);
})();
