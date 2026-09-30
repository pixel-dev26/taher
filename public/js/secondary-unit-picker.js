/**
 * Wires up the "Select Unit" modal in components/secondary-unit-picker.blade.php
 * on the Add/Edit Product forms. Reads and writes the two hidden inputs
 * (secondary_unit_of_measure, conversion_rate) that actually get submitted;
 * everything in the modal itself is scratch UI, not form fields.
 */
(function () {
    'use strict';

    var baseSelect = document.getElementById('unit_of_measure');
    var hiddenSecondary = document.getElementById('secondary_unit_of_measure');
    var hiddenRate = document.getElementById('conversion_rate');
    var hiddenClear = document.getElementById('clear_secondary_unit');
    var modalEl = document.getElementById('secondaryUnitModal');

    if (!baseSelect || !hiddenSecondary || !hiddenRate || !modalEl) {
        return;
    }

    var summary = document.querySelector('[data-secondary-unit-summary]');
    var badge = document.querySelector('[data-secondary-unit-badge]');
    var badgeText = document.querySelector('[data-secondary-unit-text]');
    var removeBtn = document.querySelector('[data-secondary-unit-remove]');

    var modalBaseDisplay = document.getElementById('modalBaseUnitDisplay');
    var modalSecondarySelect = document.getElementById('modalSecondaryUnit');
    var modalRate = document.getElementById('modalConversionRate');
    var modalRateLabelBase = document.getElementById('modalRateLabelBase');
    var modalRateLabelSecondary = document.getElementById('modalRateLabelSecondary');
    var modalError = document.getElementById('modalUnitError');
    var saveBtn = document.getElementById('modalSaveUnit');

    var priceUnitSelect = document.getElementById('price_unit');
    var priceUnitBaseOption = document.querySelector('[data-price-unit-base]');
    var priceUnitSecondaryOption = document.querySelector('[data-price-unit-secondary]');

    function tidy(n) {
        return parseFloat(n).toString();
    }

    function refresh() {
        var unit = hiddenSecondary.value;
        var rate = hiddenRate.value;
        var has = !!(unit && rate);
        if (summary) summary.classList.toggle('d-none', has);
        if (badge) badge.classList.toggle('d-none', !has);
        if (has && badgeText) {
            badgeText.textContent = '1 ' + baseSelect.value + ' = ' + tidy(rate) + ' ' + unit;
        }

        if (priceUnitBaseOption) {
            priceUnitBaseOption.textContent = baseSelect.value;
        }
        if (priceUnitSecondaryOption) {
            priceUnitSecondaryOption.textContent = unit;
            priceUnitSecondaryOption.classList.toggle('d-none', !has);
            // The secondary option just disappeared — fall back to base
            // rather than leaving an invisible option selected.
            if (!has && priceUnitSelect && priceUnitSelect.value === 'secondary') {
                priceUnitSelect.value = 'base';
            }
        }
    }

    modalEl.addEventListener('show.bs.modal', function () {
        modalBaseDisplay.value = baseSelect.value;
        modalRateLabelBase.textContent = '1 ' + baseSelect.value + ' =';
        if (hiddenSecondary.value) {
            modalSecondarySelect.value = hiddenSecondary.value;
        }
        modalRateLabelSecondary.textContent = modalSecondarySelect.value;
        modalRate.value = hiddenRate.value || '';
        modalError.classList.add('d-none');
    });

    modalSecondarySelect.addEventListener('change', function () {
        modalRateLabelSecondary.textContent = modalSecondarySelect.value;
    });

    baseSelect.addEventListener('change', function () {
        // A base-unit change while a secondary unit is already saved could
        // silently invalidate its rate's meaning, so the safest move is to
        // ask the person to re-check it rather than guess.
        if (hiddenSecondary.value) {
            hiddenSecondary.value = '';
            hiddenRate.value = '';
            if (hiddenClear) hiddenClear.value = '1';
            refresh();
        }
    });

    saveBtn.addEventListener('click', function () {
        var unit = modalSecondarySelect.value;
        var rate = parseFloat(modalRate.value);

        if (unit === baseSelect.value) {
            modalError.textContent = 'The secondary unit must be different from the base unit.';
            modalError.classList.remove('d-none');
            return;
        }
        if (!rate || rate <= 0) {
            modalError.textContent = 'Enter a conversion rate greater than 0.';
            modalError.classList.remove('d-none');
            return;
        }

        hiddenSecondary.value = unit;
        hiddenRate.value = rate;
        if (hiddenClear) hiddenClear.value = '0';
        refresh();

        if (window.bootstrap && window.bootstrap.Modal) {
            var instance = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
            instance.hide();
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            hiddenSecondary.value = '';
            hiddenRate.value = '';
            if (hiddenClear) hiddenClear.value = '1';
            refresh();
        });
    }

    refresh();
})();
