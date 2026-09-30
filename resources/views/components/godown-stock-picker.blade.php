{{--
    Adds stock for this product at one godown — used by both skus/create
    (as opening stock) and skus/edit (to add more later). Always ADDS the
    given quantity; it never sets an absolute figure. Wired up by
    public/js/godown-stock-preview.js, which shows what's already in the
    chosen godown as you pick it.
--}}
@props(['godowns', 'heading' => 'Opening Stock', 'hint' => ''])

<hr class="my-4">
<h6 class="mb-1">{{ $heading }} <span class="text-muted fw-normal">(optional)</span></h6>
@if($hint)
<p class="form-hint mt-0 mb-3">{{ $hint }}</p>
@endif
<div class="row mb-3">
    <div class="col-md-6">
        <label for="target_godown_id" class="form-label">Godown</label>
        <select class="form-select @error('target_godown_id') is-invalid @enderror" id="target_godown_id" name="target_godown_id" data-stock-url-template="{{ route('api.godown-stock', ['godown' => '__ID__']) }}">
            <option value="">— None —</option>
            @foreach($godowns as $godown)
            <option value="{{ $godown->id }}" {{ old('target_godown_id') == $godown->id ? 'selected' : '' }}>{{ $godown->code }} — {{ $godown->name }}</option>
            @endforeach
        </select>
        @error('target_godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="opening_quantity" class="form-label">Quantity to Add</label>
        <input type="number" class="form-control @error('opening_quantity') is-invalid @enderror" id="opening_quantity" name="opening_quantity" value="{{ old('opening_quantity') }}" step="0.001" min="0" inputmode="decimal">
        @error('opening_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div id="godownStockPreview" class="mb-3" hidden>
    <div class="form-hint mb-1">Already in this godown:</div>
    <div class="list-group list-group-flush border rounded" id="godownStockList" style="max-height: 220px; overflow-y: auto;"></div>
</div>
