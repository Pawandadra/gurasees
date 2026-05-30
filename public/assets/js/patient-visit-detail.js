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
    var detailBase = detailModalEl.getAttribute('data-detail-base') || '';
    var defaultPatientCode = codeInput ? codeInput.value : '';

    function isInteractive(target) {
        return Boolean(
            target.closest('a, button, input, select, textarea, label, .patient-actions, .col-actions')
        );
    }

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

    function buildDetailUrl(visitId, patientCode) {
        if (detailBase) {
            return (
                detailBase +
                (detailBase.indexOf('?') === -1 ? '?' : '&') +
                'action=visit_detail&visit_id=' +
                encodeURIComponent(String(visitId))
            );
        }

        return (
            window.location.pathname +
            '?code=' +
            encodeURIComponent(patientCode) +
            '&action=visit_detail&visit_id=' +
            encodeURIComponent(String(visitId))
        );
    }

    function openVisitDetail(visitId, patientCode) {
        visitId = parseInt(String(visitId || ''), 10);
        patientCode = String(patientCode || defaultPatientCode || '').trim();

        if (!Number.isFinite(visitId) || visitId < 1) {
            return;
        }
        if (!detailBase && !patientCode) {
            return;
        }

        if (codeInput && patientCode) {
            codeInput.value = patientCode;
        }
        if (patientCode) {
            defaultPatientCode = patientCode;
        }

        resetDetailModal();
        detailModal.show();

        fetch(buildDetailUrl(visitId, patientCode), {
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
                if (codeInput && data.patientCode) {
                    codeInput.value = data.patientCode;
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
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            openVisitDetail(
                btn.getAttribute('data-visit-id'),
                btn.getAttribute('data-patient-code') || defaultPatientCode
            );
        });
    });

    document.querySelectorAll('.visit-detail-row[data-visit-id]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (isInteractive(event.target)) {
                return;
            }
            openVisitDetail(
                row.getAttribute('data-visit-id'),
                row.getAttribute('data-patient-code') || defaultPatientCode
            );
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            if (isInteractive(event.target)) {
                return;
            }
            event.preventDefault();
            openVisitDetail(
                row.getAttribute('data-visit-id'),
                row.getAttribute('data-patient-code') || defaultPatientCode
            );
        });
    });
})();
