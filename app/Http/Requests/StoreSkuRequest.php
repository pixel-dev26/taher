<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:skus,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.key' => 'required_with:variant_attributes|string|max:100',
            'variant_attributes.*.value' => 'required_with:variant_attributes|string|max:255',
            'unit_of_measure' => 'required|string|max:20',
            'low_stock_threshold' => 'required|integer|min:0',
            'hsn_code' => 'nullable|string|max:20',
            'price' => 'required|numeric|min:0|max:9999999999',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Enter the price per unit.',
            'price.min' => 'Price cannot be negative.',
        ];
    }
}
