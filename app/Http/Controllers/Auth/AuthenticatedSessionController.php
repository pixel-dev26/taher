<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many sign-in attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Checked only after the password is right, so this message can't be
        // used to confirm which addresses have an account.
        if (!Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Contact admin.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        // Update last login timestamp — quietly, so this routine bookkeeping
        // doesn't also fire User's LogsActivity 'updated' hook on top of the
        // dedicated 'login' entry LogSuccessfulLogin already writes for the
        // Login event, which would otherwise log every sign-in twice.
        auth()->user()->updateQuietly(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
