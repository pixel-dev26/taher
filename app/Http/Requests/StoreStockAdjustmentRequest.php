<?php

namespace App\Http\Requests;

use App\Services\StockService;
use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'godown_id' => ['required', Rule::exists('godowns', 'id')->where('is_active', 1)],
            'reason' => 'required|in:initial_load,grn_correction,physical_count,damage_loss,other',
            'reason_notes' => 'required|string|max:1000',
            'reference_doc' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => ['required', Rule::exists('skus', 'id')->where('is_active', 1)],
            'items.*.quantity' => 'required|numeric|not_in:0|between:-999999999,999999999',
            'items.*.unit_price' => 'nullable|numeric|min:0|max:9999999999',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $skuIds = array_column($this->input('items', []), 'sku_id');
            if (count($skuIds) !== count(array_unique($skuIds))) {
                $validator->errors()->add('items', 'Duplicate SKUs are not allowed — combine them into one line.');
                return;
            }

            // Stock being added needs a price; stock being removed does not.
            foreach ($this->input('items', []) as $index => $item) {
                $qty = $item['quantity'] ?? null;
                $price = $item['unit_price'] ?? null;

                if (is_numeric($qty) && (float) $qty > 0 && ($price === null || $price === '')) {
                    $validator->errors()->add("items.{$index}.unit_price", 'Enter the price per unit for stock being added.');
                }
            }

            if ($validator->errors()->any()) return;

            $items = $this->input('items', []);
            $godownId = $this->input('godown_id');
            $stockService = app(StockService::class);

            foreach ($items as $index => $item) {
                $qty = (float)$item['quantity'];
                if ($qty < 0) {
                    $record = \App\Models\StockRecord::where('sku_id', $item['sku_id'])
                        ->where('godown_id', $godownId)
                        ->first();

                    $onHand = $record ? (float)$record->on_hand : 0;
                    $reserved = $record ? (float)$record->reserved : 0;
                    $adjustable = $onHand - $reserved;

                    if ($adjustable < abs($qty)) {
                        $sku = Sku::find($item['sku_id']);
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Cannot reduce {$sku->code} by " . abs($qty) . ". Max adjustable (on_hand - reserved): {$adjustable}"
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'godown_id.exists' => 'That godown is not active.',
            'items.*.sku_id.exists' => 'That product is not active.',
            'items.*.quantity.not_in' => 'Quantity cannot be 0.',
        ];
    }
}
