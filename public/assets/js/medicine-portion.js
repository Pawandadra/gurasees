(function () {
    'use strict';

    var bulkSelect = document.getElementById('portion_bulk_id');
    var availableEl = document.getElementById('portionBulkAvailable');
    if (!bulkSelect || !availableEl) {
        return;
    }

    var labelAvailable = bulkSelect.getAttribute('data-label-available') || 'Available: ';

    function updateAvailable() {
        var option = bulkSelect.options[bulkSelect.selectedIndex];
        if (!option || !option.value) {
            availableEl.hidden = true;
            availableEl.textContent = '';
            return;
        }
        var ml = parseInt(option.getAttribute('data-ml') || '0', 10);
        availableEl.textContent = labelAvailable + (Number.isFinite(ml) ? ml.toLocaleString() + ' ml' : '');
        availableEl.hidden = false;
    }

    bulkSelect.addEventListener('change', updateAvailable);
    updateAvailable();
})();
