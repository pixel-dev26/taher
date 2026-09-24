<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ensurePasswordChanged' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // The container is never reached directly — only Caddy's reverse
        // proxy can see it, over a private Docker network with no other
        // route in. So every X-Forwarded-* header on a request that reaches
        // this app is genuinely from that trusted proxy, and Laravel needs
        // it to see the real client IP (the login rate limiter is keyed on
        // it — without this every request would appear to come from
        // Caddy's own container IP) and the real scheme (https).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
