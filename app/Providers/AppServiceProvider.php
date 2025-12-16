<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add cache control headers to prevent stale pages after logout
        Response::macro('noCache', function () {
            return Response::make()
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        });
    }
}
