/**
 * Styled dropdowns.
 *
 * A native <select> popup is drawn by the operating system, so its font size and
 * appearance can't be controlled from CSS — on macOS it renders at the system
 * menu size, noticeably smaller than the page. This replaces the popup with a
 * listbox we own.
 *
 * The original <select> stays in the DOM and keeps holding the value, so form
 * submission, `required` validation, old() repopulation and any code listening
 * for 'change' all keep working untouched. It is only visually hidden — not
 * display:none, which would make a required field unfocusable and silently
 * block submission.
 *
 * On narrow screens the list opens as a bottom sheet, which is easier to hit
 * with a thumb than a dropdown pinned to the control.
 */
(function () {
    'use strict';

    var open = null; // the one menu currently open

    function selectedOption(select) {
        return select.options[select.selectedIndex] || null;
    }

    function build(select) {
        if (select.dataset.selectUi) {
            return;
        }
        select.dataset.selectUi = '1';

        var wrap = document.createElement('div');
        wrap.className = 'select-ui';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'form-select select-ui-toggle';
        toggle.setAttribute('aria-haspopup', 'listbox');
        toggle.setAttribute('aria-expanded', 'false');

        // Label lives in its own span so a long product category can ellipsis
        // rather than shoving the chevron off the edge.
        var labelEl = document.createElement('span');
        toggle.appendChild(labelEl);

        var backdrop = document.createElement('div');
        backdrop.className = 'select-ui-backdrop';
        backdrop.hidden = true;

        var menu = document.createElement('div');
        menu.className = 'select-ui-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;

        // Point the field's <label for="..."> at the control people can click.
        if (select.id) {
            var lbl = document.querySelector('label[for="' + select.id + '"]');
            if (lbl) {
                toggle.id = select.id + '-ui';
                lbl.setAttribute('for', toggle.id);
            }
        }

        wrap.appendChild(toggle);
        wrap.appendChild(backdrop);
        wrap.appendChild(menu);

        var items = [];

        function renderOptions() {
            menu.innerHTML = '';
            items = [];

            Array.prototype.forEach.call(select.options, function (opt, i) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'select-ui-option';
                item.setAttribute('role', 'option');
                item.textContent = opt.text;
                item.disabled = opt.disabled;
                item.addEventListener('click', function () {
                    choose(i);
                });
                menu.appendChild(item);
                items.push(item);
            });
        }

        function sync() {
            var opt = selectedOption(select);
            labelEl.textContent = opt ? opt.text : '';
            toggle.classList.toggle('is-placeholder', !opt || opt.value === '');
            toggle.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
            toggle.disabled = select.disabled;

            items.forEach(function (item, i) {
                var on = i === select.selectedIndex;
                item.classList.toggle('is-selected', on);
                item.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }

        function choose(i) {
            if (select.options[i] && select.options[i].disabled) {
                return;
            }
            select.selectedIndex = i;
            // Fire the same event the native control would, so existing
            // handlers (availability reloads, filter forms) still run.
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            hide();
            toggle.focus();
        }

        function show() {
            if (open && open !== hide) {
                open();
            }
            menu.hidden = false;
            backdrop.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            wrap.classList.add('is-open');

            // Flip upwards when there isn't room below (desktop dropdown only).
            var room = window.innerHeight - toggle.getBoundingClientRect().bottom;
            wrap.classList.toggle('drop-up', room < Math.min(menu.scrollHeight + 16, 280));

            var current = items[select.selectedIndex] || items[0];
            if (current) {
                current.focus({ preventScroll: true });
                current.scrollIntoView({ block: 'nearest' });
            }
            open = hide;
        }

        function hide() {
            menu.hidden = true;
            backdrop.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            wrap.classList.remove('is-open', 'drop-up');
            if (open === hide) {
                open = null;
            }
        }

        function move(from, step) {
            var i = from;
            for (var n = 0; n < items.length; n++) {
                i = (i + step + items.length) % items.length;
                if (!items[i].disabled) {
                    items[i].focus();
                    return;
                }
            }
        }

        toggle.addEventListener('click', function () {
            menu.hidden ? show() : hide();
        });

        toggle.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                show();
            }
        });

        menu.addEventListener('keydown', function (e) {
            var at = items.indexOf(document.activeElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault(); move(at, 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault(); move(at, -1);
            } else if (e.key === 'Home') {
                e.preventDefault(); move(-1, 1);
            } else if (e.key === 'End') {
                e.preventDefault(); move(0, -1);
            } else if (e.key === 'Escape' || e.key === 'Tab') {
                hide();
                if (e.key === 'Escape') { e.preventDefault(); toggle.focus(); }
            } else if (e.key.length === 1) {
                // Type a letter to jump, the way a native select behaves.
                var ch = e.key.toLowerCase();
                for (var n = 1; n <= items.length; n++) {
                    var i = ((at < 0 ? 0 : at) + n) % items.length;
                    if (!items[i].disabled && items[i].textContent.trim().toLowerCase().indexOf(ch) === 0) {
                        items[i].focus();
                        break;
                    }
                }
            }
        });

        backdrop.addEventListener('click', hide);

        document.addEventListener('click', function (e) {
            if (!menu.hidden && !wrap.contains(e.target)) {
                hide();
            }
        });

        // Keep the button in step when something else sets the value —
        // the line-item picker resets the godown this way.
        select.addEventListener('change', sync);

        renderOptions();
        sync();
    }

    function scan() {
        document.querySelectorAll('select.form-select').forEach(build);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})();
