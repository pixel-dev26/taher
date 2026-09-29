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
            // category, unit_of_measure and low_stock_threshold fall back to the
            // database's own defaults ('Other', 'Pcs', 10) when left blank — see
            // SkuController::store(), which drops empty values before create()
            // so the DB default applies instead of an empty string being saved.
            'category' => 'nullable|string|max:100',
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.key' => 'required_with:variant_attributes|string|max:100',
            'variant_attributes.*.value' => 'required_with:variant_attributes|string|max:255',
            'unit_of_measure' => 'nullable|string|max:20',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0|max:9999999999',
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Price cannot be negative.',
        ];
    }
}
