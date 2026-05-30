(function () {
    'use strict';

    var modalEl = document.getElementById('patientVisitModal');
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    var cancelBtn = document.getElementById('patientVisitCancelBtn');
    var submitBtn = document.getElementById('patientVisitSubmitBtn');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            modal.hide();
        });
    }

    modalEl.addEventListener('shown.bs.modal', function () {
        var body = modalEl.querySelector('.patient-visit-modal-body');
        if (body) {
            body.scrollTop = 0;
        }

        var firstField = document.getElementById('visited_at');
        window.requestAnimationFrame(function () {
            if (firstField && typeof firstField.focus === 'function') {
                firstField.focus({ preventScroll: true });
            } else if (cancelBtn && typeof cancelBtn.focus === 'function') {
                cancelBtn.focus({ preventScroll: true });
            }
        });
    });

    modalEl.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape' && modalEl.classList.contains('show')) {
                var openList = modalEl.querySelector('.visit-medicine-search-results:not([hidden])');
                if (openList) {
                    event.preventDefault();
                    event.stopPropagation();
                    var wrap = openList.closest('.visit-medicine-search');
                    if (wrap) {
                        wrap.dispatchEvent(new CustomEvent('search-dropdown:close'));
                    }
                    return;
                }
            }

            if (event.key !== 'Enter' || !modalEl.classList.contains('show')) {
                return;
            }

            if (cancelBtn && document.activeElement === cancelBtn) {
                event.preventDefault();
                event.stopPropagation();
                modal.hide();
            }
        },
        true
    );

    if (modalEl.getAttribute('data-open') === '1') {
        window.requestAnimationFrame(function () {
            modal.show();
        });
    } else if (modalEl.getAttribute('data-edit') === '1') {
        window.requestAnimationFrame(function () {
            modal.show();
        });
    }

    var summaryTotal = document.getElementById('summaryGrandTotal');
    var footerTotal = document.getElementById('patientVisitFooterTotal');
    if (summaryTotal && footerTotal) {
        var syncFooterTotal = function () {
            footerTotal.textContent = summaryTotal.textContent || '₹0.00';
        };
        syncFooterTotal();
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(syncFooterTotal);
            observer.observe(summaryTotal, { childList: true, characterData: true, subtree: true });
        }
    }
})();
