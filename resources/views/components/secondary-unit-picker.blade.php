{{--
    Optional second unit of measure for a product (e.g. tracked in Pieces,
    also bought/sold by the Metre). Shared by skus/create and skus/edit so
    the modal and its wiring (public/js/secondary-unit-picker.js) exist once.

    The base-unit <select> lives outside this component (id="unit_of_measure",
    already on the page); everything here just reads and reacts to it.
--}}
@props(['secondaryUnit' => null, 'conversionRate' => null, 'showClear' => false])

<label class="form-label d-block">Secondary Unit</label>
<div data-secondary-unit-summary class="{{ $secondaryUnit ? 'd-none' : '' }}">
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#secondaryUnitModal">
        <i class="bi bi-plus-lg"></i> Add secondary unit
    </button>
</div>
<div data-secondary-unit-badge class="{{ $secondaryUnit ? '' : 'd-none' }}">
    <span class="badge bg-light text-dark border" data-secondary-unit-text></span>
    <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-bs-toggle="modal" data-bs-target="#secondaryUnitModal">Edit</button>
    <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger" data-secondary-unit-remove>Remove</button>
</div>
<input type="hidden" name="secondary_unit_of_measure" id="secondary_unit_of_measure" value="{{ old('secondary_unit_of_measure', $secondaryUnit) }}">
<input type="hidden" name="conversion_rate" id="conversion_rate" value="{{ old('conversion_rate', $conversionRate) }}">
@if($showClear)
    <input type="hidden" name="clear_secondary_unit" id="clear_secondary_unit" value="0">
@endif
@error('secondary_unit_of_measure') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
@error('conversion_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

<div class="modal fade" id="secondaryUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Base Unit</label>
                        <input type="text" class="form-control" id="modalBaseUnitDisplay" readonly tabindex="-1">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="modalSecondaryUnit">Secondary Unit</label>
                        <select class="form-select" id="modalSecondaryUnit">
                            @foreach(['Pcs', 'Kgs', 'Ltrs', 'Mtrs', 'Ft', 'Nos', 'Box', 'Set', 'Roll', 'Bundle'] as $uom)
                            <option value="{{ $uom }}">{{ $uom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="form-label" for="modalConversionRate">Conversion Rate</label>
                <div class="input-group">
                    <span class="input-group-text" id="modalRateLabelBase">1 =</span>
                    <input type="number" class="form-control" id="modalConversionRate" min="0.0001" step="0.0001" inputmode="decimal">
                    <span class="input-group-text" id="modalRateLabelSecondary"></span>
                </div>
                <div class="text-danger small mt-2 d-none" id="modalUnitError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalSaveUnit">Save</button>
            </div>
        </div>
    </div>
</div>
