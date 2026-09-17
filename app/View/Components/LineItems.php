<?php

namespace App\View\Components;

use App\Models\Sku;
use App\Models\StockRecord;
use Illuminate\View\Component;

/**
 * Renders the product line-item editor shared by the GRN, dispatch, transfer
 * and adjustment forms.
 *
 * The component reads old input itself, so a validation failure no longer wipes
 * out everything the user typed. Callers do not need to pass anything back from
 * the controller.
 */
class LineItems extends Component
{
    public function __construct(
        /** Name of the godown field on the form, used to resolve availability. */
        public string $godownField = 'godown_id',
        /** Godown fixed by the caller (edit forms), overrides the form field. */
        public ?int $godownId = null,
        /** Show an availability figure and cap quantities to it. */
        public bool $showAvailable = false,
        /** Adjustments allow negative quantities to remove stock. */
        public bool $allowNegative = false,
        public string $qtyLabel = 'Quantity',
        /** Ask for a price per unit on each row (stock receipts). */
        public bool $showPrice = false,
        /** Ask for an HSN code on each row (dispatches — the Delivery Challan needs one). */
        public bool $showHsn = false,
        /** Existing rows to fall back on when there is no old input (edit forms). */
        public array $items = [],
    ) {
    }

    public function render()
    {
        $rows = $this->rows();

        return view('components.line-items', [
            'rows' => $rows,
            'nextIndex' => $this->nextIndex($rows),
        ]);
    }

    /**
     * Rebuild the rows the user had on screen.
     *
     * Old input wins over the passed-in items, so a failed edit preserves the
     * user's changes instead of silently reverting to what is stored.
     */
    protected function rows(): array
    {
        $submitted = old('items', $this->items);

        if (! is_array($submitted) || empty($submitted)) {
            return [];
        }

        $skuIds = array_filter(array_column($submitted, 'sku_id'));

        if (empty($skuIds)) {
            return [];
        }

        $skus = Sku::whereIn('id', $skuIds)->get()->keyBy('id');
        $available = $this->availability($skuIds);

        $rows = [];

        // Preserve the original index — validation errors are keyed
        // items.{index}.quantity and must line up with the input names.
        foreach ($submitted as $index => $item) {
            $sku = $skus->get($item['sku_id'] ?? null);

            if (! $sku) {
                continue;
            }

            $record = $available->get($sku->id);

            $rows[] = [
                'index' => $index,
                'sku' => $sku,
                'quantity' => $item['quantity'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                // Falls back to the product's own HSN code (Products screen)
                // when nothing has been typed here yet — one less thing to
                // retype for a product that's already classified.
                'hsn_code' => $item['hsn_code'] ?? $sku->hsn_code,
                'available' => $record
                    ? (float) $record->on_hand - (float) $record->reserved
                    : null,
            ];
        }

        return $rows;
    }

    /** Availability for every row in one query rather than one per row. */
    protected function availability(array $skuIds)
    {
        $godownId = $this->godownId ?? old($this->godownField, request($this->godownField));

        if (! $this->showAvailable || ! $godownId) {
            return collect();
        }

        return StockRecord::whereIn('sku_id', $skuIds)
            ->where('godown_id', $godownId)
            ->get()
            ->keyBy('sku_id');
    }

    /** Where the JS should continue numbering newly added rows. */
    protected function nextIndex(array $rows): int
    {
        return empty($rows) ? 0 : max(array_column($rows, 'index')) + 1;
    }
}
