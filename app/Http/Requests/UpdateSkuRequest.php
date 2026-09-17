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
        $rules = [
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
        ];

        // Deactivating (or reviving) a product is the app's one admin-only
        // data action — see routes/web.php. Without a rule the key never
        // reaches validated(), so a staff submission simply can't touch it.
        if ($this->user()->isAdmin()) {
            $rules['is_active'] = 'boolean';
        }

        return $rules;
    }
}
