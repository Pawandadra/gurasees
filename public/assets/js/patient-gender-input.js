(function () {
    'use strict';

    var ALLOWED = /^[MFO]$/;

    document.querySelectorAll('.patient-gender-input').forEach(function (input) {
        input.addEventListener('input', function () {
            var value = input.value.toUpperCase().replace(/[^MFO]/g, '');
            if (value.length > 1) {
                value = value.charAt(value.length - 1);
            }
            if (input.value !== value) {
                input.value = value;
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }
            var letter = event.key.toUpperCase();
            if (!ALLOWED.test(letter)) {
                event.preventDefault();
            }
        });

        input.addEventListener('blur', function () {
            input.value = input.value.toUpperCase();
        });
    });
})();
