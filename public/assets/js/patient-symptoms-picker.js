(function () {
    'use strict';

    var root = document.getElementById('patientSymptomsPicker');
    if (!root) {
        return;
    }

    var catalog = [];
    try {
        catalog = JSON.parse(root.getAttribute('data-symptoms') || '[]');
    } catch (e) {
        catalog = [];
    }

    var searchInput = document.getElementById('patientSymptomSearchInput');
    var searchResults = document.getElementById('patientSymptomSearchResults');
    var listEl = document.getElementById('patientSymptomList');
    var listEmptyEl = document.getElementById('patientSymptomListEmpty');
    var hiddenEl = document.getElementById('patientSymptomHiddenInputs');

    var selected = [];
    var activeIndex = -1;
    var dropdownOpen = false;

    function symptomById(id) {
        var key = String(id);
        for (var i = 0; i < catalog.length; i++) {
            if (String(catalog[i].id) === key) {
                return catalog[i];
            }
        }
        return null;
    }

    function isSelected(id) {
        return selected.indexOf(String(id)) !== -1;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function syncHiddenInputs() {
        if (!hiddenEl) {
            return;
        }
        hiddenEl.innerHTML = '';
        selected.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'symptoms[]';
            input.value = id;
            hiddenEl.appendChild(input);
        });
    }

    function renderList() {
        if (!listEl) {
            return;
        }

        listEl.innerHTML = '';
        var hasItems = selected.length > 0;

        if (listEmptyEl) {
            listEmptyEl.classList.toggle('d-none', hasItems);
        }
        listEl.classList.toggle('d-none', !hasItems);

        var sorted = selected.slice().sort(function (a, b) {
            var sa = symptomById(a);
            var sb = symptomById(b);
            return (sa ? sa.name : '').localeCompare(sb ? sb.name : '');
        });

        sorted.forEach(function (id) {
            var item = symptomById(id);
            if (!item) {
                selected = selected.filter(function (sid) {
                    return sid !== id;
                });
                return;
            }

            var li = document.createElement('li');
            li.className = 'list-group-item patient-symptom-list-item d-flex align-items-center justify-content-between gap-2';
            li.innerHTML =
                '<span class="patient-symptom-list-label">' + escapeHtml(item.name) + '</span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger patient-symptom-remove px-2" data-id="' +
                escapeHtml(id) + '" title="' +
                escapeHtml(root.getAttribute('data-label-remove') || 'Remove') + '" aria-label="' +
                escapeHtml(root.getAttribute('data-label-remove') || 'Remove') + '">×</button>';

            listEl.appendChild(li);
        });

        listEl.querySelectorAll('.patient-symptom-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sid = btn.getAttribute('data-id');
                if (!sid) {
                    return;
                }
                selected = selected.filter(function (id) {
                    return id !== sid;
                });
                renderList();
                syncHiddenInputs();
                refreshDropdown();
            });
        });

        syncHiddenInputs();
    }

    function hideSearchResults() {
        if (!searchResults) {
            return;
        }
        dropdownOpen = false;
        searchResults.hidden = true;
        searchResults.innerHTML = '';
        activeIndex = -1;
        if (searchInput) {
            searchInput.setAttribute('aria-expanded', 'false');
        }
    }

    function showSearchResults() {
        if (!searchResults) {
            return;
        }
        dropdownOpen = true;
        searchResults.hidden = false;
        if (searchInput) {
            searchInput.setAttribute('aria-expanded', 'true');
        }
    }

    function highlightActiveItem(items) {
        items.forEach(function (el, i) {
            var on = i === activeIndex;
            el.classList.toggle('active', on);
            if (on) {
                el.setAttribute('aria-selected', 'true');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.removeAttribute('aria-selected');
            }
        });
    }

    function filterSymptoms(query) {
        query = query.trim().toLowerCase();
        return catalog.filter(function (item) {
            if (isSelected(item.id)) {
                return false;
            }
            if (query === '') {
                return true;
            }
            return item.name.toLowerCase().indexOf(query) !== -1;
        });
    }

    function renderSearchResults(results) {
        if (!searchResults) {
            return;
        }
        searchResults.innerHTML = '';

        if (results.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'visit-medicine-search-empty';
            empty.textContent = root.getAttribute('data-label-empty') || 'No symptoms found';
            searchResults.appendChild(empty);
            activeIndex = -1;
            showSearchResults();
            return;
        }

        results.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'visit-medicine-search-item';
            btn.setAttribute('role', 'option');
            btn.setAttribute('data-id', String(item.id));
            btn.innerHTML = '<span class="visit-medicine-search-name">' + escapeHtml(item.name) + '</span>';
            btn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            btn.addEventListener('click', function () {
                addSymptom(item.id);
            });
            searchResults.appendChild(btn);
        });

        showSearchResults();
    }

    function refreshDropdown() {
        if (!searchInput || !dropdownOpen) {
            return;
        }

        renderSearchResults(filterSymptoms(searchInput.value));

        var items = searchResults.querySelectorAll('.visit-medicine-search-item');
        if (items.length === 0) {
            activeIndex = -1;
            return;
        }

        if (activeIndex >= items.length) {
            activeIndex = items.length - 1;
        }
        if (activeIndex < 0) {
            activeIndex = 0;
        }
        highlightActiveItem(items);
    }

    function addSymptom(id) {
        var key = String(id);
        if (isSelected(key)) {
            return;
        }

        selected.push(key);
        renderList();
        syncHiddenInputs();
        refreshDropdown();

        if (searchInput) {
            searchInput.focus();
        }
    }

    if (searchInput) {
        var searchWrap = document.getElementById('patientSymptomSearchWrap');

        function openDropdown() {
            renderSearchResults(filterSymptoms(searchInput.value));
            var items = searchResults.querySelectorAll('.visit-medicine-search-item');
            if (items.length > 0) {
                activeIndex = 0;
                highlightActiveItem(items);
            }
        }

        searchInput.addEventListener('input', function () {
            if (!dropdownOpen) {
                openDropdown();
                return;
            }
            refreshDropdown();
        });

        searchInput.addEventListener('focus', openDropdown);
        searchInput.addEventListener('click', function () {
            if (!dropdownOpen) {
                openDropdown();
            }
        });

        searchInput.addEventListener(
            'keydown',
            function (event) {
                if (!dropdownOpen) {
                    if (event.key === 'ArrowDown' || event.key === 'Enter') {
                        event.preventDefault();
                        event.stopPropagation();
                        openDropdown();
                    }
                    return;
                }

                var items = searchResults.querySelectorAll('.visit-medicine-search-item');
                if (items.length === 0) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        event.stopPropagation();
                        hideSearchResults();
                    }
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    event.stopPropagation();
                    activeIndex = activeIndex < 0 ? 0 : Math.min(activeIndex + 1, items.length - 1);
                    highlightActiveItem(items);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    event.stopPropagation();
                    activeIndex = activeIndex < 0 ? items.length - 1 : Math.max(activeIndex - 1, 0);
                    highlightActiveItem(items);
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    event.stopPropagation();
                    if (activeIndex < 0) {
                        activeIndex = 0;
                    }
                    addSymptom(items[activeIndex].getAttribute('data-id'));
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    hideSearchResults();
                }
            },
            true
        );

        if (searchWrap) {
            searchWrap.addEventListener('search-dropdown:close', hideSearchResults);
        }
    }

    var initial = [];
    try {
        initial = JSON.parse(root.getAttribute('data-initial') || '[]');
    } catch (e) {
        initial = [];
    }
    initial.forEach(function (line) {
        var id = String(line.symptom_id || '');
        if (id && !isSelected(id)) {
            selected.push(id);
        }
    });

    renderList();

    var form = root.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            syncHiddenInputs();
        });
    }
})();
