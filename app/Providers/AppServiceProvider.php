<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Cloudflare Tunnel terminates TLS and forwards plain HTTP to Nginx.
        // Without this, asset() and redirects resolve to http:// and the
        // browser blocks the Inertia XHR as mixed content.
        if (
            request()->header('x-forwarded-proto') === 'https'
            || request()->isSecure()
            || str_starts_with((string) config('app.url'), 'https://')
        ) {
            URL::forceScheme('https');
        }
    }
}
