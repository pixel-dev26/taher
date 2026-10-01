<?php

namespace App\Http\Requests\Concerns;

use App\Models\Sku;

/**
 * Quantity and price convert to the base unit independently of each other:
 *
 * - Quantity follows the per-row unit choice (items.*.unit — 'base' or
 *   'secondary', from the selector in resources/views/components/line-items
 *   .blade.php): whichever unit the person picked for that line.
 * - Price is always treated as quoted per secondary unit whenever the
 *   product has one — that's this business's pricing convention (e.g. pipe
 *   priced per Mtr even though it's counted and moved in whole Pcs), not a
 *   per-line choice, so it does not depend on items.*.unit at all.
 *
 * Both land on the same base-unit numbers everywhere downstream (rules,
 * controllers, StockService) regardless of how either was entered.
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
            if (!$sku || !$sku->hasSecondaryUnit()) {
                continue;
            }

            if (($item['unit'] ?? 'base') === 'secondary' && isset($item['quantity']) && is_numeric($item['quantity'])) {
                $items[$i]['quantity'] = $sku->toBaseQuantity((float) $item['quantity'], 'secondary');
            }

            if (isset($item['unit_price']) && is_numeric($item['unit_price'])) {
                $items[$i]['unit_price'] = $sku->toBaseUnitPrice((float) $item['unit_price'], 'secondary');
            }
        }

        $this->merge(['items' => $items]);
    }
}
