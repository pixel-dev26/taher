<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Left blank, these keep their current value rather than being
            // cleared — see SkuController::update(), which drops empty values
            // from the validated data before calling $sku->update().
            'category' => 'nullable|string|max:100',
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.key' => 'required_with:variant_attributes|string|max:100',
            'variant_attributes.*.value' => 'required_with:variant_attributes|string|max:255',
            'unit_of_measure' => 'nullable|string|max:20',
            'secondary_unit_of_measure' => 'nullable|string|max:20|required_with:conversion_rate|different:unit_of_measure',
            'conversion_rate' => 'nullable|numeric|gt:0|max:999999.9999|required_with:secondary_unit_of_measure',
            'weight' => 'nullable|numeric|min:0|max:999999.999',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string|max:20',
            // Optional here: most existing products were created before prices.
            'price' => 'nullable|numeric|min:0|max:9999999999',
            // Adds stock at a godown — left blank (the usual case), this is a
            // no-op; see SkuController::update(). Always in the base unit.
            'target_godown_id' => ['nullable', 'required_with:opening_quantity', Rule::exists('godowns', 'id')->where('is_active', 1)],
            'opening_quantity' => 'nullable|required_with:target_godown_id|numeric|gt:0|max:999999999',
        ];

        // Deactivating (or reviving) a product is the app's one admin-only
        // data action — see routes/web.php. Without a rule the key never
        // reaches validated(), so a staff submission simply can't touch it.
        if ($this->user()->isAdmin()) {
            $rules['is_active'] = 'boolean';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'target_godown_id.required_with' => 'Choose a godown for the quantity, or clear it.',
            'target_godown_id.exists' => 'That godown is not active.',
            'opening_quantity.required_with' => 'Enter the quantity to add, or clear the godown.',
            'opening_quantity.gt' => 'Quantity must be greater than 0.',
        ];
    }
}
