(function () {
    'use strict';

    var detailModalEl = document.getElementById('visitDetailModal');
    if (!detailModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
    var bodyEl = document.getElementById('visitDetailModalBody');
    var footerEl = document.getElementById('visitDetailModalFooter');
    var editBtn = document.getElementById('visitDetailEditBtn');
    var deleteIdInput = document.getElementById('visitDeleteId');
    var deleteBtn = document.getElementById('visitDeleteBtn');
    var codeInput = document.querySelector('#visitDeleteForm input[name="code"]');
    var patientCode = codeInput ? codeInput.value : '';

    function resetDetailModal() {
        if (bodyEl) {
            bodyEl.innerHTML = '<p class="text-muted mb-0">Loading…</p>';
        }
        if (footerEl) {
            footerEl.classList.add('d-none');
        }
        if (editBtn) {
            editBtn.classList.add('d-none');
            editBtn.setAttribute('href', '#');
        }
        if (deleteBtn) {
            deleteBtn.classList.add('d-none');
        }
    }

    function openVisitDetail(visitId) {
        if (!visitId || !patientCode) {
            return;
        }

        resetDetailModal();
        detailModal.show();

        var url =
            window.location.pathname +
            '?code=' +
            encodeURIComponent(patientCode) +
            '&action=visit_detail&visit_id=' +
            encodeURIComponent(String(visitId));

        fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) {
                        throw new Error(data.message || 'Failed to load visit');
                    }

                    return data;
                });
            })
            .then(function (data) {
                if (bodyEl) {
                    bodyEl.innerHTML = data.html || '';
                }
                if (footerEl) {
                    footerEl.classList.remove('d-none');
                }
                if (editBtn && data.canEdit && data.editUrl) {
                    editBtn.classList.remove('d-none');
                    editBtn.setAttribute('href', data.editUrl);
                }
                if (deleteBtn && data.canDelete) {
                    deleteBtn.classList.remove('d-none');
                    if (deleteIdInput) {
                        deleteIdInput.value = String(visitId);
                    }
                    deleteBtn.setAttribute(
                        'data-confirm',
                        deleteBtn.getAttribute('data-confirm') || 'Delete this visit?'
                    );
                }
            })
            .catch(function () {
                if (bodyEl) {
                    bodyEl.innerHTML =
                        '<p class="alert alert-danger mb-0">Could not load visit details.</p>';
                }
            });
    }

    document.querySelectorAll('.visit-detail-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var visitId = btn.getAttribute('data-visit-id');
            openVisitDetail(visitId);
        });
    });
})();
