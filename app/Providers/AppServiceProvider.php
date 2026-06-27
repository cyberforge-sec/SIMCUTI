<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SupabaseService::class, function ($app) {
            return new SupabaseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS for all generated URLs.
        // Behind Cloudflare Tunnel, the origin receives HTTP but the public URL is HTTPS.
        // This ensures route(), asset(), etc. always generate https:// URLs.
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
   