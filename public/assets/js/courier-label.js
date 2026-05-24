(function () {
    'use strict';

    var printBtn = document.getElementById('courierLabelPrint');
    var cancelBtn = document.getElementById('courierLabelCancel');

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            var backUrl = cancelBtn.getAttribute('data-back-url') || '/courier.php';
            window.close();
            window.setTimeout(function () {
                window.location.href = backUrl;
            }, 120);
        });
    }
})();
