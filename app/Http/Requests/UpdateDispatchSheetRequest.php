<?php

namespace App\Http\Requests;

use App\Services\StockService;
use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDispatchSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->route('dispatch_sheet')->status === 'pending';
    }

    public function rules(): array
    {
        // Only a *changed* delivery date has to be today or later. An overdue
        // pending sheet keeps its stored (past) date, and the user must still
        // be able to fix a quantity or vehicle number on it without being
        // forced to falsify the date.
        $stored = $this->route('dispatch_sheet')->delivery_date?->format('Y-m-d');
        $dateChanged = $this->input('delivery_date') !== $stored;

        return [
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|gt:0|max:999999999',
            'items.*.unit_price' => 'required|numeric|min:0|max:9999999999',
            'items.*.hsn_code' => 'required|string|max:20',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_gstin' => 'nullable|string|max:20',
            'place_of_supply' => 'nullable|string|max:100',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_date' => ['nullable', 'date', Rule::when($dateChanged, ['after_or_equal:today'])],
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
            'lr_no' => 'nullable|string|max:100',
            'eway_no' => 'nullable|string|max:50',
            'transport_name' => 'nullable|string|max:255',
            'transport_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $items = $this->input('items', []);
            $sheet = $this->route('dispatch_sheet');
            $stockService = app(StockService::class);

            $skuIds = array_column($items, 'sku_id');
            if (count($skuIds) !== count(array_unique($skuIds))) {
                $validator->errors()->add('items', 'Duplicate SKUs are not allowed.');
                return;
            }

            // Get old items for comparison
            $oldItems = $sheet->items->pluck('quantity', 'sku_id')->map(fn($q) => (float)$q)->toArray();

            foreach ($items as $index => $item) {
                $newQty = (float)$item['quantity'];
                $oldQty = $oldItems[$item['sku_id']] ?? 0;
                $delta = $newQty - $oldQty;

                if ($delta > 0) {
                    $available = $stockService->getAvailable($item['sku_id'], $sheet->godown_id);
                    if ($available < $delta) {
                        $sku = Sku::find($item['sku_id']);
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Insufficient stock for {$sku->code}. Additional available: {$available}"
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.sku_id.required' => 'Please select a SKU for each item.',
            'items.*.quantity.gt' => 'Quantity must be greater than 0.',
            'items.*.unit_price.required' => 'Enter the rate per unit for each item.',
            'items.*.unit_price.min' => 'Rate cannot be negative.',
            'items.*.hsn_code.required' => 'Enter the HSN code for each item.',
        ];
    }
}
