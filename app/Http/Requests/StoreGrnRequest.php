<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $minDate = now()->subDays(7)->format('Y-m-d');

        return [
            'godown_id' => 'required|exists:godowns,id',
            'receipt_date' => "required|date|before_or_equal:today|after_or_equal:{$minDate}",
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0|max:9999999999',
            'challan_no' => 'nullable|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.sku_id.required' => 'Please select a SKU for each item.',
            'items.*.sku_id.exists' => 'Invalid SKU selected.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.gt' => 'Quantity must be greater than 0.',
            'items.*.unit_price.required' => 'Enter the price per unit for each item.',
            'items.*.unit_price.numeric' => 'Price must be a number.',
            'items.*.unit_price.min' => 'Price cannot be negative.',
            'items.*.unit_price.max' => 'Price is too large.',
        ];
    }
}
