(function () {
    'use strict';

    var addressEl = document.getElementById('address');
    var deliveryEl = document.getElementById('delivery_address');
    var sameCheck = document.getElementById('delivery_same_as_address');

    if (!addressEl || !deliveryEl || !sameCheck) {
        return;
    }

    function setSynced(on) {
        if (on) {
            deliveryEl.value = addressEl.value;
            deliveryEl.readOnly = true;
            deliveryEl.classList.add('delivery-address-synced');
        } else {
            deliveryEl.readOnly = false;
            deliveryEl.classList.remove('delivery-address-synced');
        }
    }

    sameCheck.addEventListener('change', function () {
        setSynced(sameCheck.checked);
    });

    addressEl.addEventListener('input', function () {
        if (sameCheck.checked) {
            deliveryEl.value = addressEl.value;
        }
    });

    if (sameCheck.checked) {
        setSynced(true);
    }
})();
