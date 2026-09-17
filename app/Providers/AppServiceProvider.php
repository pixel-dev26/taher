<?php

namespace App\Providers;

use App\Database\ImmediateSQLiteConnection;
use App\Models\DispatchSheet;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new ImmediateSQLiteConnection($connection, $database, $prefix, $config);
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Coarse per-IP backstops for the guest auth forms (the login
        // controller adds a finer per-account lockout on top). Named, so each
        // form has its own bucket: the framework's default guest signature is
        // domain + IP only, and a shared bucket meant five wrong passwords
        // would also lock the user out of "Forgot password".
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Behind a TLS-terminating proxy the app sees plain HTTP and would
        // otherwise generate http:// links and redirects on the live site.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // The bottom tab bar shows a badge for work waiting to go out, so the
        // count has to be available on every page, not just the dashboard.
        View::composer('layouts.app', function ($view) {
            $view->with(
                'pendingDispatchCount',
                auth()->check() ? DispatchSheet::pending()->count() : 0
            );
        });

        // No explicit Event::listen() here: Laravel auto-discovers the
        // listeners in app/Listeners by their handle() type-hints.
        // Registering them again here double-fires them.
    }
}
