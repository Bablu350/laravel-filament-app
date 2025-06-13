<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;


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
    public function boot(Request $request): void
    {
        // Fix for default string length in older MySQL
        Schema::defaultStringLength(191);

        // Force HTTPS in production (important on Render)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // If needed: configure session domain dynamically
            Config::set('session.domain', $request->getHost());
        }

        // Ensure Livewire temp storage directory exists
        $tmpDir = storage_path('app/livewire-tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
