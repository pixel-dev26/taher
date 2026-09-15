/**
 * Shared product picker and line-item editor.
 *
 * Replaces five near-identical copies that previously lived in @push('scripts')
 * blocks on the GRN, dispatch (create and edit), transfer and adjustment forms.
 *
 * Behaviour is driven by data attributes, so the same file serves every form:
 *   #skuSearchInput[data-sku-picker][data-godown-field][data-require-godown]
 *   #lineItems[data-next-index][data-show-available][data-allow-negative]
 *   #lineItemTemplate  — the row markup, cloned per product
 */
(function () {
    'use strict';

    var picker = document.querySelector('[data-sku-picker]');
    var list = document.getElementById('skuSearchResults');
    var container = document.getElementById('lineItems');
    var template = document.getElementById('lineItemTemplate');
    var emptyHint = document.getElementById('noItemsHint');

    if (!picker || !list || !container || !template) {
        return;
    }

    var form = container.closest('form');
    var godownField = picker.dataset.godownField || 'godown_id';
    var requireGodown = picker.dataset.requireGodown === '1';
    var showAvailable = container.dataset.showAvailable === '1';

    var nextIndex = parseInt(container.dataset.nextIndex, 10) || 0;
    var request = null;      // in-flight fetch, aborted when the user types again
    var debounce = null;
    var results = [];
    var activeIndex = -1;
    var submitting = false;

    // ---------------------------------------------------------------- helpers

    function godownEl() {
        return document.getElementById(godownField) ||
            (form && form.querySelector('[name="' + godownField + '"]'));
    }

    function godownValue() {
        var el = godownEl();
        return el ? el.value : '';
    }

    function rows() {
        return Array.prototype.slice.call(container.querySelectorAll('.line-item'));
    }

    function hasSku(id) {
        return rows().some(function (row) {
            return row.dataset.skuId === String(id);
        });
    }

    function tidy(n) {
        return parseFloat(n).toString();
    }

    /** Reuses the layout's toast container instead of a blocking alert(). */
    function toast(message, type) {
        var host = document.querySelector('.toast-container');
        if (!host) {
            host = document.createElement('div');
            host.className = 'toast-container';
            document.body.appendChild(host);
        }

        var el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (type || 'secondary');
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';
        host.appendChild(el);

        if (window.bootstrap && window.bootstrap.Toast) {
            new window.bootstrap.Toast(el, { delay: 4000 }).show();
            el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        } else {
            setTimeout(function () { el.remove(); }, 4000);
        }
    }

    function refreshState() {
        var count = rows().length;

        if (emptyHint) {
            emptyHint.style.display = count ? 'none' : 'block';
        }

        var summary = document.getElementById('lineItemsSummary');
        if (summary) {
            var total = rows().reduce(function (sum, row) {
                var v = parseFloat(row.querySelector('input[type=number]').value);
                return sum + (isNaN(v) ? 0 : v);
            }, 0);
            summary.textContent = count
                ? count + (count === 1 ? ' product' : ' products') + ' · ' + tidy(total.toFixed(3)) + ' total'
                : 'No products added yet';
        }

        updateSteps();
    }

    function updateSteps() {
        var steps = document.querySelectorAll('.step-indicator .step');
        if (steps.length < 3) {
            return;
        }
        var ready = !requireGodown || godownValue() !== '';
        var hasItems = rows().length > 0;

        steps[0].className = 'step' + (ready ? ' done' : ' active');
        steps[1].className = 'step' + (hasItems ? ' done' : (ready ? ' active' : ''));
        steps[2].className = 'step' + (hasItems ? ' active' : '');
    }

    // ---------------------------------------------------------------- search

    function closeList() {
        list.style.display = 'none';
        list.innerHTML = '';
        picker.setAttribute('aria-expanded', 'false');
        picker.removeAttribute('aria-activedescendant');
        results = [];
        activeIndex = -1;
    }

    function search() {
        var q = picker.value.trim();

        if (requireGodown && !godownValue()) {
            closeList();
            toast('Please choose a godown first — availability depends on it.', 'warning');
            return;
        }

        if (q.length < 1) {
            closeList();
            return;
        }

        // Abort the previous request so slow responses can't overwrite newer ones.
        if (request) {
            request.abort();
        }
        request = new AbortController();

        var url = '/api/sku-search?q=' + encodeURIComponent(q);
        if (godownValue()) {
            url += '&godown_id=' + encodeURIComponent(godownValue());
        }

        fetch(url, { signal: request.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { render(data.items || [], data.total || 0, data.limit || 20); })
            .catch(function (e) {
                if (e.name !== 'AbortError') {
                    toast('Could not search products. Check your connection.', 'danger');
                }
            });
    }

    function render(items, total, limit) {
        results = items;
        activeIndex = -1;
        list.innerHTML = '';

        if (!items.length) {
            list.innerHTML = '<div class="list-group-item text-muted small text-center">No matching products found</div>';
            list.style.display = 'block';
            picker.setAttribute('aria-expanded', 'true');
            return;
        }

        items.forEach(function (sku, i) {
            var added = hasSku(sku.id);
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'list-group-item list-group-item-action sku-result' + (added ? ' sku-result-added' : '');
            row.id = 'sku-opt-' + i;
            row.setAttribute('role', 'option');

            // Code and name on separate lines — the names are long and
            // near-identical, so a single truncated line is unreadable.
            var meta = '';
            if (showAvailable && typeof sku.available !== 'undefined') {
                meta = '<span class="badge ' + (sku.available > 0 ? 'badge-dispatched' : 'badge-cancelled') + '">' +
                    tidy(sku.available) + ' ' + sku.uom + '</span>';
            }

            row.innerHTML =
                '<div class="sku-result-top"><code>' + sku.code + '</code>' +
                (sku.category ? '<span class="sku-result-cat">' + sku.category + '</span>' : '') +
                meta + '</div>' +
                '<div class="sku-result-name">' + sku.name + '</div>' +
                (added ? '<div class="sku-result-flag">Already added</div>' : '');

            row.addEventListener('click', function (e) {
                e.preventDefault();
                choose(i);
            });

            list.appendChild(row);
        });

        if (total > limit) {
            var more = document.createElement('div');
            more.className = 'sku-result-more';
            more.textContent = 'Showing ' + items.length + ' of ' + total + ' — keep typing to narrow it down';
            list.appendChild(more);
        }

        list.style.display = 'block';
        picker.setAttribute('aria-expanded', 'true');
    }

    function highlight(i) {
        var options = list.querySelectorAll('.sku-result');
        if (!options.length) {
            return;
        }
        activeIndex = (i + options.length) % options.length;
        options.forEach(function (o) { o.classList.remove('active'); });
        var el = options[activeIndex];
        el.classList.add('active');
        el.scrollIntoView({ block: 'nearest' });
        picker.setAttribute('aria-activedescendant', el.id);
    }

    function choose(i) {
        var sku = results[i];
        if (!sku) {
            return;
        }
        if (hasSku(sku.id)) {
            toast(sku.code + ' is already on this list.', 'warning');
            return;
        }
        addRow(sku);
        picker.value = '';
        closeList();
        picker.focus();
    }

    // ------------------------------------------------------------- line items

    function addRow(sku) {
        var html = template.innerHTML
            .replace(/__I__/g, nextIndex)
            .replace(/__SKU_ID__/g, sku.id)
            .replace(/__CODE__/g, sku.code)
            .replace(/__NAME__/g, sku.name)
            .replace(/__UOM__/g, sku.uom)
            .replace(/__AVAIL__/g, typeof sku.available !== 'undefined' ? tidy(sku.available) : '');

        var frag = document.createElement('div');
        frag.innerHTML = html.trim();
        var row = frag.firstElementChild;

        var qty = row.querySelector('input[type=number]');
        if (showAvailable && typeof sku.available !== 'undefined') {
            qty.max = sku.available;
        }

        container.appendChild(row);
        nextIndex++;
        container.dataset.nextIndex = nextIndex;

        row.classList.add('line-item-new');
        setTimeout(function () { row.classList.remove('line-item-new'); }, 1200);

        refreshState();
        qty.focus();
    }

    /** Returns true when every row holds a usable quantity. */
    function validate(report) {
        var firstBad = null;

        rows().forEach(function (row) {
            var input = row.querySelector('input[type=number]');
            var value = parseFloat(input.value);
            var max = parseFloat(input.max);
            var bad = isNaN(value) || value === 0 || (!isNaN(max) && value > max);
            var slot = row.querySelector('.li-error');

            input.classList.toggle('is-invalid', !!(bad && report));
            row.classList.toggle('line-item-invalid', !!(bad && report));

            if (slot) {
                if (bad && report) {
                    slot.textContent = isNaN(value) || value === 0
                        ? 'Enter a quantity.'
                        : 'Only ' + tidy(max) + ' available.';
                    slot.hidden = false;
                } else {
                    slot.hidden = true;
                }
            }

            if (bad && !firstBad) {
                firstBad = input;
            }
        });

        return firstBad;
    }

    // ---------------------------------------------------------------- events

    picker.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(search, 300);
    });

    picker.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlight(activeIndex + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlight(activeIndex - 1);
        } else if (e.key === 'Enter') {
            // Never let the picker submit the form.
            e.preventDefault();
            if (activeIndex >= 0) {
                choose(activeIndex);
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.sku-picker')) {
            closeList();
        }
    });

    container.addEventListener('click', function (e) {
        var btn = e.target.closest('.li-remove');
        if (!btn) {
            return;
        }
        btn.closest('.line-item').remove();
        refreshState();
    });

    container.addEventListener('input', function (e) {
        if (e.target.matches('input[type=number]')) {
            validate(true);
            refreshState();
        }
    });

    // Changing the godown invalidates every availability figure on screen.
    // The old code silently emptied the table; ask first, and put the previous
    // selection back if the user declines.
    var godown = godownEl();
    if (godown) {
        godown.dataset.previous = godown.value;
        godown.addEventListener('change', function () {
            var count = rows().length;
            if (count && !confirm('Changing the godown will clear the ' + count + ' product' + (count === 1 ? '' : 's') + ' you have added. Continue?')) {
                godown.value = godown.dataset.previous;
                return;
            }
            godown.dataset.previous = godown.value;
            container.innerHTML = '';
            nextIndex = 0;
            closeList();
            refreshState();
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!rows().length) {
                e.preventDefault();
                toast('Add at least one product before saving.', 'warning');
                return;
            }
            var bad = validate(true);
            if (bad) {
                e.preventDefault();
                bad.focus();
                bad.scrollIntoView({ block: 'center', behavior: 'smooth' });
                toast('Check the highlighted quantities.', 'warning');
                return;
            }
            submitting = true;
        });
    }

    // A back-swipe on a phone would otherwise silently discard a long entry.
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && rows().length) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    refreshState();
})();
