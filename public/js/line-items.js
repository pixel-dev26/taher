/**
 * Shared product picker and line-item editor.
 *
 * Replaces five near-identical copies that previously lived in @push('scripts')
 * blocks on the GRN, dispatch (create and edit), transfer and adjustment forms.
 *
 * Behaviour is driven by data attributes, so the same file serves every form:
 *   #skuSearchInput[data-sku-picker][data-search-url][data-godown-field][data-require-godown]
 *   #lineItems[data-next-index][data-show-available][data-allow-negative][data-show-price][data-show-hsn]
 *   #lineItemTemplate  — the row markup, cloned per product
 *
 * Product names, codes and categories are typed by staff, so they only ever
 * reach the page through textContent / .value — never through innerHTML or a
 * string replace into markup.
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
    var searchUrl = picker.dataset.searchUrl;
    var godownField = picker.dataset.godownField || 'godown_id';
    var requireGodown = picker.dataset.requireGodown === '1';
    var showAvailable = container.dataset.showAvailable === '1';
    var showPrice = container.dataset.showPrice === '1';
    var showHsn = container.dataset.showHsn === '1';

    var nextIndex = parseInt(container.dataset.nextIndex, 10) || 0;
    var request = null;      // in-flight fetch, aborted when the user types again
    var debounce = null;
    var results = [];
    var activeIndex = -1;
    var submitting = false;
    var dirty = false;       // anything typed or added since the page loaded

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

    function qtyInput(row) {
        return row.querySelector('.li-qty input');
    }

    function priceInput(row) {
        return row.querySelector('.li-price input');
    }

    function hsnInput(row) {
        return row.querySelector('.li-hsn input');
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (typeof text !== 'undefined') {
            node.textContent = text;
        }
        return node;
    }

    /** Rupees with Indian grouping, matching App\Support\Money::inr(). */
    function rupees(n) {
        return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /** A negative correction removes stock, which takes no price. */
    function isRemoving(row) {
        return parseFloat(qtyInput(row).value) < 0;
    }

    /** Quantity x price for a row, or null until both are filled in. */
    function rowAmount(row) {
        var price = priceInput(row);
        if (!price || price.value === '' || isRemoving(row)) {
            return null;
        }
        var q = parseFloat(qtyInput(row).value);
        var p = parseFloat(price.value);
        return isNaN(q) || isNaN(p) ? null : q * p;
    }

    /**
     * Hide the price box while a row removes stock. Disabling it as well keeps
     * the browser's required-field check from blocking the save on a field
     * the user cannot see.
     */
    function syncPrice(row) {
        var price = priceInput(row);
        if (!price) {
            return;
        }
        var removing = isRemoving(row);
        price.closest('.li-price').hidden = removing;
        price.disabled = removing;
        var amount = row.querySelector('.li-amount');
        if (amount) {
            amount.hidden = removing;
        }
    }

    /** Reuses the layout's toast container instead of a blocking alert(). */
    function toast(message, type) {
        var host = document.querySelector('.toast-container');
        if (!host) {
            host = el('div', 'toast-container');
            document.body.appendChild(host);
        }

        var node = el('div', 'toast align-items-center border-0 text-bg-' + (type || 'secondary'));
        node.setAttribute('role', 'alert');

        var wrap = el('div', 'd-flex');
        wrap.appendChild(el('div', 'toast-body', message));
        var close = el('button', 'btn-close btn-close-white me-2 m-auto');
        close.type = 'button';
        close.setAttribute('data-bs-dismiss', 'toast');
        wrap.appendChild(close);
        node.appendChild(wrap);
        host.appendChild(node);

        if (window.bootstrap && window.bootstrap.Toast) {
            new window.bootstrap.Toast(node, { delay: 4000 }).show();
            node.addEventListener('hidden.bs.toast', function () { node.remove(); });
        } else {
            setTimeout(function () { node.remove(); }, 4000);
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
                var v = parseFloat(qtyInput(row).value);
                return sum + (isNaN(v) ? 0 : v);
            }, 0);
            var text = count
                ? count + (count === 1 ? ' product' : ' products') + ' · ' + tidy(total.toFixed(3)) + ' total'
                : 'No products added yet';

            if (showPrice && count) {
                var value = rows().reduce(function (sum, row) {
                    return sum + (rowAmount(row) || 0);
                }, 0);
                text += ' · ' + rupees(value);
            }

            summary.textContent = text;
        }

        if (showPrice) {
            rows().forEach(function (row) {
                syncPrice(row);
                var slot = row.querySelector('.li-amount strong');
                var amount = rowAmount(row);
                if (slot) {
                    slot.textContent = amount === null ? '—' : rupees(amount);
                }
            });
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

        var url = searchUrl + '?q=' + encodeURIComponent(q);
        if (showAvailable && godownValue()) {
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
            list.appendChild(el('div', 'list-group-item text-muted small text-center', 'No matching products found'));
            list.style.display = 'block';
            picker.setAttribute('aria-expanded', 'true');
            return;
        }

        items.forEach(function (sku, i) {
            var added = hasSku(sku.id);
            var row = el('button', 'list-group-item list-group-item-action sku-result' + (added ? ' sku-result-added' : ''));
            row.type = 'button';
            row.id = 'sku-opt-' + i;
            row.setAttribute('role', 'option');

            // Code and name on separate lines — the names are long and
            // near-identical, so a single truncated line is unreadable.
            var top = el('div', 'sku-result-top');
            top.appendChild(el('code', null, sku.code));
            if (sku.category) {
                top.appendChild(el('span', 'sku-result-cat', sku.category));
            }
            if (showAvailable && typeof sku.available !== 'undefined') {
                top.appendChild(el('span', 'badge ' + (sku.available > 0 ? 'badge-dispatched' : 'badge-cancelled'),
                    tidy(sku.available) + ' ' + sku.uom));
            }
            row.appendChild(top);
            row.appendChild(el('div', 'sku-result-name', sku.name));
            if (added) {
                row.appendChild(el('div', 'sku-result-flag', 'Already added'));
            }

            row.addEventListener('click', function (e) {
                e.preventDefault();
                choose(i);
            });

            list.appendChild(row);
        });

        if (total > limit) {
            list.appendChild(el('div', 'sku-result-more',
                'Showing ' + items.length + ' of ' + total + ' — keep typing to narrow it down'));
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
        var node = options[activeIndex];
        node.classList.add('active');
        node.scrollIntoView({ block: 'nearest' });
        picker.setAttribute('aria-activedescendant', node.id);
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
        // Only the two numeric placeholders go through the markup string;
        // everything user-entered is assigned as text below.
        var html = template.innerHTML
            .replace(/__I__/g, String(nextIndex))
            .replace(/__SKU_ID__/g, String(parseInt(sku.id, 10)));

        var frag = document.createElement('div');
        frag.innerHTML = html.trim();
        var row = frag.firstElementChild;

        row.querySelector('.li-code').textContent = sku.code;
        row.querySelector('.li-name').textContent = sku.name;
        row.querySelectorAll('.li-uom').forEach(function (node) { node.textContent = sku.uom; });

        var avail = row.querySelector('.li-avail-qty');
        if (avail) {
            avail.textContent = typeof sku.available !== 'undefined' ? tidy(sku.available) : '';
        }

        var hsn = hsnInput(row);
        if (hsn) {
            hsn.value = sku.hsn_code || '';
        }

        var remove = row.querySelector('.li-remove');
        if (remove) {
            remove.setAttribute('aria-label', 'Remove ' + sku.code);
        }

        var qty = qtyInput(row);
        if (showAvailable && typeof sku.available !== 'undefined') {
            qty.max = sku.available;
        }

        container.appendChild(row);
        nextIndex++;
        container.dataset.nextIndex = nextIndex;
        dirty = true;

        row.classList.add('line-item-new');
        setTimeout(function () { row.classList.remove('line-item-new'); }, 1200);

        refreshState();
        qty.focus();
    }

    /**
     * Returns the first input needing attention, or null when every row holds
     * a usable quantity (and price and HSN code, where asked for).
     *
     * A problem is only flagged on a row the user has typed in, or on save
     * ($strict) — otherwise entering a quantity on one row would immediately
     * flag every other empty row. Rows the user hasn't touched keep whatever
     * message the server rendered on them after a failed save.
     */
    function validate(report, strict) {
        var firstBad = null;

        rows().forEach(function (row) {
            var input = qtyInput(row);
            var price = priceInput(row);
            var hsn = hsnInput(row);

            var touched = !!strict || [input, price, hsn].some(function (node) {
                return node && node.dataset.touched === '1';
            });
            var show = !!report && touched;

            var value = parseFloat(input.value);
            var max = parseFloat(input.max);
            var qtyBad = isNaN(value) || value === 0 || (!isNaN(max) && value > max);

            var priceValue = price ? parseFloat(price.value) : 0;
            var priceBad = !!price && !(value < 0) && (price.value === '' || isNaN(priceValue) || priceValue < 0);

            var hsnBad = !!hsn && !(value < 0) && hsn.value.trim() === '';

            if (!firstBad && (qtyBad || priceBad || hsnBad)) {
                firstBad = qtyBad ? input : (priceBad ? price : hsn);
            }

            if (!show) {
                return;
            }

            var messages = [];

            input.classList.toggle('is-invalid', qtyBad);
            if (price) {
                price.classList.toggle('is-invalid', priceBad);
            }
            if (hsn) {
                hsn.classList.toggle('is-invalid', hsnBad);
            }
            row.classList.toggle('line-item-invalid', qtyBad || priceBad || hsnBad);

            if (qtyBad) {
                messages.push(isNaN(value) || value === 0
                    ? 'Enter a quantity.'
                    : 'Only ' + tidy(max) + ' available.');
            }
            if (priceBad) {
                messages.push(priceValue < 0 ? 'Price cannot be negative.' : 'Enter the price per unit.');
            }
            if (hsnBad) {
                messages.push('Enter the HSN code.');
            }

            var slot = row.querySelector('.li-error');
            if (slot) {
                slot.textContent = messages.join(' ');
                slot.hidden = messages.length === 0;
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
        dirty = true;
        refreshState();
    });

    container.addEventListener('input', function (e) {
        if (e.target.matches('.li-qty input, .li-price input, .li-hsn input')) {
            e.target.dataset.touched = '1';
            dirty = true;
            validate(true);
            refreshState();
        }
    });

    // Only when rows carry godown-specific figures (availability, quantity
    // caps) does changing the godown invalidate what's on screen. The GRN and
    // correction forms don't, so a wrong godown there is just a dropdown fix
    // rather than 25 rows to retype.
    var godown = godownEl();
    if (godown && showAvailable) {
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
    } else if (godown) {
        godown.addEventListener('change', updateSteps);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!rows().length) {
                e.preventDefault();
                toast('Add at least one product before saving.', 'warning');
                return;
            }
            var bad = validate(true, true);
            if (bad) {
                e.preventDefault();
                bad.focus();
                bad.scrollIntoView({ block: 'center', behavior: 'smooth' });
                var what = showPrice && showHsn
                    ? 'quantities, prices and HSN codes'
                    : (showPrice ? 'quantities and prices' : 'quantities');
                toast('Check the highlighted ' + what + '.', 'warning');
                return;
            }
            submitting = true;
        });

        // The form's own Cancel link is a deliberate exit, not an accident.
        form.querySelectorAll('a.btn').forEach(function (link) {
            link.addEventListener('click', function () { submitting = true; });
        });
    }

    // A back-swipe on a phone would otherwise silently discard a long entry —
    // but only once something has actually been entered or changed.
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && dirty && rows().length) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    refreshState();
})();
