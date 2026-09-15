<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGodownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:godowns,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'contact_phone' => 'nullable|string|max:20',
        ];
    }
}
