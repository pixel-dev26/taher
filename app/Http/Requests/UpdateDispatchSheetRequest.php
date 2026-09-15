<?php

namespace App\Http\Requests;

use App\Services\StockService;
use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->route('dispatch_sheet')->status === 'pending';
    }

    public function rules(): array
    {
        return [
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
}
