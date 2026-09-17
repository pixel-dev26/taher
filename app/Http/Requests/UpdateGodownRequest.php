<?php

namespace App\Http\Requests;

use App\Models\DispatchSheet;
use App\Models\StockRecord;
use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGodownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'contact_phone' => 'nullable|string|max:20',
        ];

        // Closing a godown hides its stock from every screen and report, so
        // like retiring a product it is admin-only; staff can't submit it.
        if ($this->user()->isAdmin()) {
            $rules['is_active'] = 'boolean';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $godown = $this->route('godown');

            if (! $this->has('is_active') || $this->boolean('is_active') || ! $godown->is_active) {
                return;
            }

            $holdsStock = StockRecord::where('godown_id', $godown->id)
                ->where(fn ($q) => $q->where('on_hand', '>', 0)->orWhere('reserved', '>', 0))
                ->exists();

            $hasPendingDocs = DispatchSheet::pending()->where('godown_id', $godown->id)->exists()
                || StockTransfer::pending()
                    ->where(fn ($q) => $q->where('source_godown_id', $godown->id)->orWhere('dest_godown_id', $godown->id))
                    ->exists();

            if ($holdsStock || $hasPendingDocs) {
                $validator->errors()->add(
                    'is_active',
                    'Cannot deactivate a godown that still holds stock or has pending dispatches/transfers. Transfer the stock out and clear pending documents first.'
                );
            }
        });
    }
}
