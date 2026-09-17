<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        if ($request->filled('current_password') || $request->filled('password')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'current_password' => ['required', 'current_password'],
                'password' => [
                    'required', 'confirmed', Password::min(8)->mixedCase()->numbers(),
                    function ($attribute, $value, $fail) use ($user) {
                        if (Hash::check($value, $user->password)) {
                            $fail('The new password must be different from your current password.');
                        }
                    },
                ],
            ]);

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->invalidateOtherSessions($request->session()->getId());
            if (auth()->viaRemember()) {
                auth()->login($user, true);
            }

            return back()->with('success', 'Account updated and password changed successfully.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Account updated successfully.');
    }
}
