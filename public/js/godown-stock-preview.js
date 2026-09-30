/**
 * On the Add Product form, shows what's already stored in the godown chosen
 * for the opening-stock section (resources/views/skus/create.blade.php).
 * Purely informational — it never affects what gets submitted.
 */
(function () {
    'use strict';

    var select = document.getElementById('target_godown_id');
    var panel = document.getElementById('godownStockPreview');
    var list = document.getElementById('godownStockList');

    if (!select || !panel || !list) {
        return;
    }

    var urlTemplate = select.dataset.stockUrlTemplate;
    var request = null;

    function tidy(n) {
        return parseFloat(n).toString();
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

    function render(items) {
        list.innerHTML = '';

        if (!items.length) {
            list.appendChild(el('div', 'list-group-item text-muted small', 'Nothing in this godown yet.'));
            return;
        }

        items.forEach(function (item) {
            var row = el('div', 'list-group-item d-flex justify-content-between align-items-center py-2');
            var left = el('div');
            left.appendChild(el('code', 'me-2', item.code));
            left.appendChild(document.createTextNode(item.name));
            row.appendChild(left);
            row.appendChild(el('span', 'text-muted small text-nowrap ms-2', tidy(item.on_hand) + ' ' + item.uom));
            list.appendChild(row);
        });
    }

    select.addEventListener('change', function () {
        var id = select.value;

        if (request) {
            request.abort();
        }

        if (!id) {
            panel.hidden = true;
            return;
        }

        panel.hidden = false;
        list.innerHTML = '';
        list.appendChild(el('div', 'list-group-item text-muted small', 'Loading…'));

        request = new AbortController();
        var url = urlTemplate.replace('__ID__', encodeURIComponent(id));

        fetch(url, { signal: request.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { render(data.items || []); })
            .catch(function (e) {
                if (e.name !== 'AbortError') {
                    list.innerHTML = '';
                    list.appendChild(el('div', 'list-group-item text-danger small', 'Could not load — check your connection.'));
                }
            });
    });
})();
