@props([
    'godownField' => 'godown_id',
    'requireGodown' => false,
    'showAvailable' => false,
    'placeholder' => 'Type product name or code to add...',
    'hint' => 'Start typing, then tap a product — or use ↑ ↓ and Enter',
])

<div class="mb-3 position-relative sku-picker">
    {{-- Plain input-group: input-group-lg forced 20px text and a 48px box,
         which was the only control on the page off the shared scale. --}}
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text"
               class="form-control"
               id="skuSearchInput"
               autocomplete="off"
               role="combobox"
               aria-expanded="false"
               aria-controls="skuSearchResults"
               aria-autocomplete="list"
               placeholder="{{ $placeholder }}"
               data-sku-picker
               data-search-url="{{ route('api.sku-search') }}"
               data-godown-field="{{ $godownField }}"
               data-require-godown="{{ $requireGodown ? '1' : '0' }}"
               data-show-available="{{ $showAvailable ? '1' : '0' }}">
    </div>
    <div class="form-hint">{{ $hint }}</div>
    <div id="skuSearchResults" class="sku-search-results list-group" role="listbox" aria-label="Matching products"></div>
</div>
