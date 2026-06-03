(function () {
    'use strict';

    document.querySelectorAll('.phone-country-picker').forEach(initPicker);

    function initPicker(root) {
        var hidden = root.querySelector('input[type="hidden"]');
        var trigger = root.querySelector('.phone-country-trigger');
        var valueEl = root.querySelector('.phone-country-value');
        var menu = root.querySelector('.phone-country-menu');
        var options = Array.from(menu.querySelectorAll('[role="option"]'));
        var hiddenName = hidden.getAttribute('name') || 'phone_iso';
        var phoneInputName = hiddenName.endsWith('_iso')
            ? hiddenName.slice(0, -4)
            : 'phone';
        var phoneInput = root.closest('.phone-input-group')?.querySelector(
            'input[name="' + phoneInputName + '"]'
        );

        var open = false;
        var filter = '';
        var activeIndex = -1;

        function setOpen(next) {
            open = next;
            menu.hidden = !open;
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.classList.toggle('is-open', open);

            if (open) {
                filter = '';
                applyFilter();
                scrollToSelected();
            } else {
                filter = '';
                options.forEach(function (opt) {
                    opt.hidden = false;
                });
            }
        }

        function applyFilter() {
            var q = filter.toLowerCase().trim();
            var firstVisible = -1;

            options.forEach(function (opt, index) {
                var match = q === '' || opt.dataset.search.indexOf(q) !== -1;
                opt.hidden = !match;
                if (match && firstVisible === -1) {
                    firstVisible = index;
                }
            });

            activeIndex = firstVisible;
            highlightActive();
        }

        function highlightActive() {
            options.forEach(function (opt, index) {
                opt.classList.toggle('active', index === activeIndex && !opt.hidden);
            });

            var active = options[activeIndex];
            if (active) {
                active.scrollIntoView({ block: 'nearest' });
            }
        }

        function moveActive(direction) {
            var visible = options
                .map(function (opt, index) {
                    return { index: index, hidden: opt.hidden };
                })
                .filter(function (item) {
                    return !item.hidden;
                });

            if (!visible.length) {
                return;
            }

            var current = visible.findIndex(function (item) {
                return item.index === activeIndex;
            });

            if (current === -1) {
                current = direction > 0 ? -1 : 0;
            }

            var next = current + direction;
            if (next < 0) {
                next = visible.length - 1;
            }
            if (next >= visible.length) {
                next = 0;
            }

            activeIndex = visible[next].index;
            highlightActive();
        }

        function updatePhoneInput(iso) {
            if (!phoneInput) {
                return;
            }
            phoneInput.removeAttribute('placeholder');
            phoneInput.maxLength = iso === 'IN' ? 10 : 14;
        }

        function select(opt) {
            hidden.value = opt.dataset.iso;
            valueEl.textContent = opt.dataset.compact;

            options.forEach(function (o) {
                o.setAttribute('aria-selected', o === opt ? 'true' : 'false');
            });

            updatePhoneInput(opt.dataset.iso);
            setOpen(false);
            phoneInput?.focus();
        }

        function scrollToSelected() {
            var selected = options.find(function (opt) {
                return opt.dataset.iso === hidden.value;
            });

            if (!selected) {
                return;
            }

            activeIndex = options.indexOf(selected);
            highlightActive();
            selected.scrollIntoView({ block: 'nearest' });
        }

        root.addEventListener('phone-country:close', function () {
            setOpen(false);
        });

        trigger.addEventListener('click', function () {
            setOpen(!open);
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                select(opt);
            });
        });

        root.addEventListener('keydown', function (event) {
            if (!open && (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                setOpen(true);
                return;
            }

            if (!open) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                trigger.focus();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveActive(1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                var active = options[activeIndex];
                if (active && !active.hidden) {
                    select(active);
                }
                return;
            }

            if (event.key === 'Backspace') {
                event.preventDefault();
                filter = filter.slice(0, -1);
                applyFilter();
                return;
            }

            if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                filter += event.key;
                applyFilter();
            }
        });

        updatePhoneInput(hidden.value);
    }
})();
