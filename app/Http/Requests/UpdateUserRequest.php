<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => 'required|in:admin,staff',
            'is_active' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');

            // An admin editing their own account can't remove their own admin
            // role or deactivate themselves — either one would lock them out
            // with no other admin able to undo it.
            if ($user->id === auth()->id()) {
                if ($this->input('role') !== 'admin') {
                    $validator->errors()->add('role', 'You cannot remove your own admin role.');
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
                }

                return;
            }

            // Demoting or deactivating a DIFFERENT admin is also blocked if
            // they're the last other active admin — otherwise the self-guard
            // above only prevents locking *yourself* out, and two admins
            // could still leave the app with none between them.
            $demoting = $user->isAdmin() && $this->input('role') !== 'admin';
            $deactivating = $user->isAdmin() && ! $this->boolean('is_active');

            if ($demoting || $deactivating) {
                $otherActiveAdmins = User::admins()->active()->where('id', '!=', $user->id)->count();

                if ($otherActiveAdmins < 1) {
                    $message = 'This is the only other admin account — demoting or deactivating it would leave no one able to manage Users. Promote someone else to admin first.';
                    $validator->errors()->add($demoting ? 'role' : 'is_active', $message);
                }
            }
        });
    }
}
