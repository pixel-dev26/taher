<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSkuRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    /** Set once withValidator's check passes — SkuController::update() records this as who approved the edit. */
    public ?User $approvingAdmin = null;

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * A product with a secondary unit is always priced per that unit (e.g.
     * per Mtr, not per Pcs) — there's no choice to make here, so this always
     * converts to the base unit when a rate is in effect. The rate is
     * whatever this submission leaves in effect: the rate typed in this same
     * request if given, otherwise the product's existing one (blank here
     * means "keep the current secondary unit", same as everywhere else on
     * this form).
     */
    protected function prepareForValidation(): void
    {
        if (!is_numeric($this->input('price'))) {
            return;
        }

        $rate = $this->input('conversion_rate');
        $rate = is_numeric($rate) && (float) $rate > 0
            ? (float) $rate
            : (float) ($this->route('sku')?->conversion_rate ?? 0);

        if ($rate > 0) {
            $this->merge(['price' => round(((float) $this->input('price')) * $rate, 2)]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            // Every product edit needs an admin's sign-off, entered fresh
            // here — not just whoever is already logged in. See withValidator().
            'admin_password' => 'required|string',
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
            'opening_quantity' => 'nullable|required_with:target_godown_id|integer|gt:0|max:999999999',
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
            'opening_quantity.integer' => 'Quantity must be a whole number.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Everything else about the edit should be valid before this
            // (rate-limited) check runs — no point spending an attempt, and
            // no point asking again, on a submission that will fail anyway.
            if ($validator->errors()->any()) {
                return;
            }

            // No admin identifies themselves here — just a password — so the
            // throttle key is the requester (whoever is logged in) plus IP,
            // not an email, and checking it means testing it against every
            // active admin rather than one looked-up account.
            $key = Str::transliterate('sku-approval|' . $this->user()->id . '|' . $this->ip());

            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                $seconds = RateLimiter::availableIn($key);
                $validator->errors()->add('admin_password', "Too many attempts. Please try again in {$seconds} seconds.");
                return;
            }

            $password = (string) $this->input('admin_password');
            $admin = User::where('role', 'admin')->where('is_active', true)->get()
                ->first(fn ($user) => Hash::check($password, $user->password));

            if (!$admin) {
                RateLimiter::hit($key, self::LOCKOUT_SECONDS);
                $validator->errors()->add('admin_password', 'Incorrect admin password.');
                return;
            }

            RateLimiter::clear($key);
            $this->approvingAdmin = $admin;
        });
    }
}
