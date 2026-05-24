(function () {
    'use strict';

    var DISMISS_MS = 3000;

    function dismissAlert(alertEl) {
        alertEl.style.transition = 'opacity 0.35s ease';
        alertEl.style.opacity = '0';
        window.setTimeout(function () {
            alertEl.remove();
        }, 350);
    }

    document.querySelectorAll('.alert-success').forEach(function (alertEl) {
        window.setTimeout(function () {
            dismissAlert(alertEl);
        }, DISMISS_MS);
    });
})();
