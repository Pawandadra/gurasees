(function () {
    'use strict';

    function isInteractive(target) {
        return Boolean(
            target.closest(
                'a, button, input, select, textarea, label, .patient-actions, .col-actions, .reception-sort-link'
            )
        );
    }

    function navigateRow(row) {
        var href = row.getAttribute('data-href');
        if (href) {
            window.location.href = href;
        }
    }

    document.querySelectorAll('tr.reception-table-row-link[data-href]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (isInteractive(event.target)) {
                return;
            }
            navigateRow(row);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            if (isInteractive(event.target)) {
                return;
            }
            event.preventDefault();
            navigateRow(row);
        });
    });
})();
