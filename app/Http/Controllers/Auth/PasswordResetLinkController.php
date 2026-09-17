<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Same reply whether or not the address has an account, so this form
        // can't be used to discover which emails are valid logins.
        $neutral = 'If that email address belongs to an account, a password reset link has been sent to it.';

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            report($e);

            // The broker writes the token row before it tries to send, and
            // its 60-second throttle would then refuse a retry even though
            // nothing went out. Clear it so a fixed mail server can be
            // retried immediately.
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'The reset email could not be sent right now. Please contact your administrator to reset your password.',
            ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return back()->with('status', $neutral);
    }
}
