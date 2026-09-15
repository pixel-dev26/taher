<?php

namespace App\Http\Requests;

use App\Services\StockService;
use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'godown_id' => 'required|exists:godowns,id',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'customer_name' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_date' => 'nullable|date|after_or_equal:today',
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $items = $this->input('items', []);
            $godownId = $this->input('godown_id');
            $stockService = app(StockService::class);

            // Check for duplicate SKUs
            $skuIds = array_column($items, 'sku_id');
            if (count($skuIds) !== count(array_unique($skuIds))) {
                $validator->errors()->add('items', 'Duplicate SKUs are not allowed.');
                return;
            }

            // Check stock availability
            foreach ($items as $index => $item) {
                $available = $stockService->getAvailable($item['sku_id'], $godownId);
                if ($available < (float)$item['quantity']) {
                    $sku = Sku::find($item['sku_id']);
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Insufficient stock for {$sku->code}. Available: {$available}"
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.sku_id.required' => 'Please select a SKU for each item.',
            'items.*.quantity.gt' => 'Quantity must be greater than 0.',
        ];
    }
}
