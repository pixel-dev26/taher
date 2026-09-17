<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Deactivating a user (from the Users screen) should take effect right
     * away. Without this, only the login form checks is_active, so someone
     * already signed in keeps working until their session naturally expires.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->is_active) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been deactivated. Contact admin.',
            ]);
        }

        return $next($request);
    }
}
