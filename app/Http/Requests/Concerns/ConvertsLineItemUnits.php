<?php

namespace App\Http\Requests\Concerns;

use App\Models\Sku;

/**
 * Each line item can be entered in a product's base or secondary unit
 * (items.*.unit — 'base' or 'secondary', from the per-row selector in
 * resources/views/components/line-items.blade.php). This runs before
 * validation and rewrites quantity/unit_price to the base-unit equivalent,
 * so every rule, the controller, and StockService only ever see base-unit
 * numbers — nothing downstream needs to know a secondary unit was used.
 *
 * Done server-side, not in JS, because quantity and price directly become
 * stock and money: a tampered or buggy client must not be able to submit a
 * number in one unit while claiming another.
 */
trait ConvertsLineItemUnits
{
    protected function convertLineItemUnitsToBase(): void
    {
        $items = $this->input('items');
        if (!is_array($items)) {
            return;
        }

        $skuIds = array_filter(array_column($items, 'sku_id'));
        if (!$skuIds) {
            return;
        }

        $skus = Sku::whereIn('id', $skuIds)->get()->keyBy('id');

        foreach ($items as $i => $item) {
            $sku = $skus->get($item['sku_id'] ?? null);
            $unit = $item['unit'] ?? 'base';

            if (!$sku || !$sku->hasSecondaryUnit() || $unit !== 'secondary') {
                continue;
            }

            if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                $items[$i]['quantity'] = $sku->toBaseQuantity((float) $item['quantity'], 'secondary');
            }
            if (isset($item['unit_price']) && is_numeric($item['unit_price'])) {
                $items[$i]['unit_price'] = $sku->toBaseUnitPrice((float) $item['unit_price'], 'secondary');
            }
        }

        $this->merge(['items' => $items]);
    }
}
