<?php

namespace App\Providers;

use App\Models\DispatchSheet;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // The bottom tab bar shows a badge for work waiting to go out, so the
        // count has to be available on every page, not just the dashboard.
        View::composer('layouts.app', function ($view) {
            $view->with(
                'pendingDispatchCount',
                auth()->check() ? DispatchSheet::pending()->count() : 0
            );
        });

        // No explicit Event::listen() here: Laravel auto-discovers
        // App\Listeners\LogSuccessfulLogin by its handle(Login $event)
        // type-hint. Registering it again here double-fires it.
    }
}
