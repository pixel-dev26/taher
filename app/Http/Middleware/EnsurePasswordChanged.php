<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            $allowed = ['change-password', 'logout'];
            $currentPath = $request->path();

            foreach ($allowed as $path) {
                if (str_contains($currentPath, $path)) {
                    return $next($request);
                }
            }

            return redirect('/change-password')->with('warning', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}
