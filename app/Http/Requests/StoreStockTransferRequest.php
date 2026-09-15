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
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'source_godown_id.different' => 'Source and destination godowns must be different.',
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
