<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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
        Paginator::useTailwind();

        // Behind Render's HTTPS proxy, force all generated URLs (incl. Vite
        // asset links) to https so the browser doesn't block them as mixed
        // content. Without this, CSS/JS fail to load and the page is unstyled.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', function (...$segments) {
            return implode('', array_map(static fn ($segment) => (string) $segment, $segments));
        });
    }
}
