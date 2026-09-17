<?php

namespace App\Http\Requests;

use App\Services\StockService;
use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'source_godown_id' => 'required|exists:godowns,id|different:dest_godown_id',
            'dest_godown_id' => 'required|exists:godowns,id',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0|max:9999999999',
            'items.*.hsn_code' => 'required|string|max:20',
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

    public function messages(): array
    {
        return [
            'source_godown_id.different' => 'Source and destination godowns must be different.',
            'items.*.unit_price.required' => 'Enter the rate per unit for each item.',
            'items.*.unit_price.min' => 'Rate cannot be negative.',
            'items.*.hsn_code.required' => 'Enter the HSN code for each item.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $sourceGodownId = $this->input('source_godown_id');
            $items = $this->input('items', []);
            $stockService = app(StockService::class);

            foreach ($items as $index => $item) {
                $available = $stockService->getAvailable($item['sku_id'], $sourceGodownId);
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
}
