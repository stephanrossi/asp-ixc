<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
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
        Http::macro('IXC', function () {
            return Http::withBasicAuth(env('IXC_API_ID'), env('IXC_API_TOKEN'))
                ->baseUrl(env('IXC_API_URL'));
        });
    }
}
