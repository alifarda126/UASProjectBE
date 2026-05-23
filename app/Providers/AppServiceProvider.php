<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- WAJIB ADA UNTUK FORCE HTTPS

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
        // Langsung paksa semua aset, route, dan redirect Socialite menggunakan HTTPS secara absolut
        URL::forceScheme('https');
    }
}