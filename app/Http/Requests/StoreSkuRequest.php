<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * A product with a secondary unit is always priced per that unit (e.g.
     * per Mtr, not per Pcs) — there's no choice to make here, so this always
     * converts to the base unit when a rate is present. Sku::price itself is
     * always a base-unit price everywhere else it's read.
     */
    protected function prepareForValidation(): void
    {
        if (!is_numeric($this->input('price'))) {
            return;
        }

        $rate = $this->input('conversion_rate');
        if (is_numeric($rate) && (float) $rate > 0) {
            $this->merge(['price' => round(((float) $this->input('price')) * (float) $rate, 2)]);
        }
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
            // Both required together: a rate with no unit (or vice versa)
            // can't be converted to or from.
            'secondary_unit_of_measure' => 'nullable|string|max:20|required_with:conversion_rate|different:unit_of_measure',
            'conversion_rate' => 'nullable|numeric|gt:0|max:999999.9999|required_with:secondary_unit_of_measure',
            'weight' => 'nullable|numeric|min:0|max:999999.999',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0|max:9999999999',
            // Opening stock at creation — optional, and always in the base
            // unit (see SkuController::store()). Picking a godown with no
            // quantity (or vice versa) is almost certainly a mistake, so
            // each requires the other rather than silently doing nothing.
            'target_godown_id' => ['nullable', 'required_with:opening_quantity', Rule::exists('godowns', 'id')->where('is_active', 1)],
            'opening_quantity' => 'nullable|required_with:target_godown_id|integer|gt:0|max:999999999',
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Price cannot be negative.',
            'secondary_unit_of_measure.different' => 'The secondary unit must be different from the base unit.',
            'secondary_unit_of_measure.required_with' => 'Choose a secondary unit, or clear the conversion rate.',
            'conversion_rate.required_with' => 'Enter the conversion rate, or clear the secondary unit.',
            'target_godown_id.required_with' => 'Choose a godown for the opening quantity, or clear it.',
            'target_godown_id.exists' => 'That godown is not active.',
            'opening_quantity.required_with' => 'Enter the opening quantity, or clear the godown.',
            'opening_quantity.gt' => 'Opening quantity must be greater than 0.',
            'opening_quantity.integer' => 'Opening quantity must be a whole number.',
        ];
    }
}
