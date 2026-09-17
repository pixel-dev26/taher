<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    public function change(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required', 'confirmed', Password::min(8)->mixedCase()->numbers(),
                // The whole point of a forced first-login change is that the
                // admin who handed out the temporary password shouldn't know
                // the real one — so re-entering the temporary one doesn't count.
                function ($attribute, $value, $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('The new password must be different from your current password.');
                    }
                },
            ],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        $user->invalidateOtherSessions($request->session()->getId());
        if (auth()->viaRemember()) {
            auth()->login($user, true);
        }

        return redirect('/dashboard')->with('success', 'Password changed successfully!');
    }
}
