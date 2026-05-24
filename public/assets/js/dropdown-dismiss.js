(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.phone-country-picker.is-open').forEach(function (picker) {
            if (!picker.contains(event.target)) {
                picker.dispatchEvent(new CustomEvent('phone-country:close'));
            }
        });

        document.querySelectorAll('.header-patient-search, .visit-medicine-search').forEach(function (wrap) {
            var results = wrap.querySelector('[role="listbox"]');
            if (!results || results.hidden) {
                return;
            }
            if (!wrap.contains(event.target)) {
                wrap.dispatchEvent(new CustomEvent('search-dropdown:close'));
            }
        });
    });
})();
