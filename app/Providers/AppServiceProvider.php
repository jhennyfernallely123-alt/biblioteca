<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        $path = storage_path('app/i18n');
        File::ensureDirectoryExists($path);
        app('translator')->getLoader()->addJsonPath($path);

        if (is_file($bundle = resource_path('cacert.pem'))) {
            Http::globalOptions(['verify' => $bundle]);
        }
    }
}
