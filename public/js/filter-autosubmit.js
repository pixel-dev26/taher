/**
 * Auto-submits filter forms when a dropdown inside them changes, so picking
 * a value from a <select> filters the list immediately — no need to also
 * click the Filter/Search/Show button.
 *
 * Opt-in via [data-autosubmit-filters] on the <form>, so this never touches
 * an unrelated select (e.g. the godown picker on a create form). Text inputs,
 * dates and checkboxes are left alone — submitting on every keystroke would
 * fight the user rather than help them; the button still covers those.
 *
 * Delegated on document so it also works with the custom dropdown in
 * select.js, which swaps in a styled button/menu but keeps the real <select>
 * in the DOM and dispatches a genuine, bubbling 'change' event on it.
 *
 * Uses requestSubmit() rather than submit(), so any required field (e.g. the
 * date range on the Stock Ledger report) still runs native validation
 * instead of silently submitting an incomplete form.
 */
(function () {
    'use strict';

    document.addEventListener('change', function (e) {
        if (!e.target.matches('select')) {
            return;
        }

        var form = e.target.closest('form[data-autosubmit-filters]');
        if (!form) {
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
