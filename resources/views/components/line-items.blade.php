{{--
    Product line-item editor.

    One markup definition serves both paths: rows restored after a validation
    failure are rendered by the @foreach below, and rows added by the user are
    cloned from the <template> at the bottom. Each row is a self-contained block
    that reflows to a single column on a phone, so there is no horizontal
    scrolling to reach the quantity field.

    The quantity field and the remove button sit in their own flex row aligned
    on their bottom edge, and any error is a full-width sibling — otherwise the
    button centres against the whole block and floats above the input.
--}}
<div id="lineItems"
     class="line-items"
     data-next-index="{{ $nextIndex }}"
     data-show-available="{{ $showAvailable ? '1' : '0' }}"
     data-allow-negative="{{ $allowNegative ? '1' : '0' }}">

    @foreach($rows as $row)
        @php
            $i = $row['index'];
            $sku = $row['sku'];
            $avail = $row['available'];
        @endphp
        <div class="line-item @error("items.{$i}.quantity") line-item-invalid @enderror"
             id="item-row-{{ $i }}"
             data-sku-id="{{ $sku->id }}">

            <input type="hidden" name="items[{{ $i }}][sku_id]" value="{{ $sku->id }}">

            <div class="li-main">
                <code class="li-code">{{ $sku->code }}</code>
                <div class="li-name">{{ $sku->name }}</div>
                @if($showAvailable && $avail !== null)
                    <div class="li-avail">Available: <strong>{{ rtrim(rtrim(number_format($avail, 3, '.', ''), '0'), '.') }}</strong> {{ $sku->unit_of_measure }}</div>
                @endif
            </div>

            <div class="li-controls">
                <div class="li-qty">
                    <label class="cl-label" for="qty-{{ $i }}">{{ $qtyLabel }}</label>
                    <div class="input-group">
                        <input type="number"
                               id="qty-{{ $i }}"
                               name="items[{{ $i }}][quantity]"
                               class="form-control @error("items.{$i}.quantity") is-invalid @enderror"
                               value="{{ $row['quantity'] }}"
                               step="0.001"
                               @if(! $allowNegative) min="0.001" @endif
                               @if($showAvailable && $avail !== null) max="{{ $avail }}" @endif
                               required>
                        <span class="input-group-text">{{ $sku->unit_of_measure }}</span>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-danger btn-icon li-remove" aria-label="Remove {{ $sku->code }}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            @error("items.{$i}.quantity")
                <div class="li-error invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    @endforeach
</div>

<div id="noItemsHint" class="empty-state" @if(count($rows)) style="display:none;" @endif>
    <i class="bi bi-inbox"></i>
    <div class="empty-text">No products added yet</div>
    <div class="empty-hint">Use the search box above to find and add products</div>
</div>

@error('items')
    <div class="text-danger small mb-3"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
@enderror

{{-- Cloned by public/js/line-items.js for each newly added product. --}}
<template id="lineItemTemplate">
    <div class="line-item" id="item-row-__I__" data-sku-id="__SKU_ID__">
        <input type="hidden" name="items[__I__][sku_id]" value="__SKU_ID__">

        <div class="li-main">
            <code class="li-code">__CODE__</code>
            <div class="li-name">__NAME__</div>
            @if($showAvailable)
                <div class="li-avail">Available: <strong>__AVAIL__</strong> __UOM__</div>
            @endif
        </div>

        <div class="li-controls">
            <div class="li-qty">
                <label class="cl-label" for="qty-__I__">{{ $qtyLabel }}</label>
                <div class="input-group">
                    <input type="number"
                           id="qty-__I__"
                           name="items[__I__][quantity]"
                           class="form-control"
                           step="0.001"
                           @if(! $allowNegative) min="0.001" @endif
                           required>
                    <span class="input-group-text">__UOM__</span>
                </div>
            </div>

            <button type="button" class="btn btn-outline-danger btn-icon li-remove" aria-label="Remove product">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="li-error invalid-feedback d-block" hidden></div>
    </div>
</template>
