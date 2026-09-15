<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.key' => 'required_with:variant_attributes|string|max:100',
            'variant_attributes.*.value' => 'required_with:variant_attributes|string|max:255',
            'unit_of_measure' => 'required|string|max:20',
            'low_stock_threshold' => 'required|integer|min:0',
            'hsn_code' => 'nullable|string|max:20',
            // Optional here: most existing products were created before prices.
            'price' => 'nullable|numeric|min:0|max:9999999999',
            'is_active' => 'boolean',
        ];
    }
}
