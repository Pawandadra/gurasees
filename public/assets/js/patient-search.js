(function () {
    'use strict';

    var wrap = document.getElementById('headerPatientSearch');
    var input = document.getElementById('headerPatientSearchInput');
    var resultsEl = document.getElementById('headerPatientSearchResults');

    if (!wrap || !input || !resultsEl) {
        return;
    }

    var searchUrl = wrap.getAttribute('data-search-url');
    if (!searchUrl) {
        return;
    }

    var debounceTimer = null;
    var activeIndex = -1;
    var items = [];

    function hideResults() {
        resultsEl.hidden = true;
        resultsEl.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        items = [];
    }

    function showResults() {
        resultsEl.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function renderResults(results) {
        resultsEl.innerHTML = '';
        items = results;
        activeIndex = -1;

        if (results.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'header-patient-search-empty';
            empty.textContent = wrap.getAttribute('data-empty-label') || 'No patients found';
            resultsEl.appendChild(empty);
            showResults();
            return;
        }

        results.forEach(function (item, index) {
            var btn = document.createElement('a');
            btn.className = 'header-patient-search-item';
            btn.href = item.url;
            btn.setAttribute('role', 'option');
            var phoneLine = item.phone
                ? '<span class="header-patient-search-phone">' + escapeHtml(item.phone) + '</span>'
                : '';
            btn.innerHTML =
                '<span class="header-patient-search-code">' + escapeHtml(item.code) + '</span>' +
                '<span class="header-patient-search-name">' + escapeHtml(item.name) + '</span>' +
                phoneLine;
            btn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            resultsEl.appendChild(btn);
        });

        showResults();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function fetchResults(query) {
        var url = searchUrl + '?q=' + encodeURIComponent(query);
        fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('search failed');
                }
                return response.json();
            })
            .then(function (data) {
                renderResults(data.results || []);
            })
            .catch(function () {
                hideResults();
            });
    }

    function setActive(index) {
        var links = resultsEl.querySelectorAll('.header-patient-search-item');
        links.forEach(function (link, i) {
            link.classList.toggle('active', i === index);
        });
        activeIndex = index;
    }

    input.addEventListener('input', function () {
        var query = input.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            hideResults();
            return;
        }

        debounceTimer = setTimeout(function () {
            fetchResults(query);
        }, 250);
    });

    input.addEventListener('keydown', function (event) {
        var links = resultsEl.querySelectorAll('.header-patient-search-item');
        if (!links.length) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(Math.min(activeIndex + 1, links.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            window.location.href = links[activeIndex].href;
        } else if (event.key === 'Escape') {
            hideResults();
        }
    });

    document.addEventListener('click', function (event) {
        if (!wrap.contains(event.target)) {
            hideResults();
        }
    });
})();
