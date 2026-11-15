<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)
            ->by(hash('sha256', (string) $request->ip()))
            ->response(fn () => redirect()->route('login')->withErrors([
                'username' => 'Terlalu banyak permintaan masuk dari jaringan ini. Tunggu 1 menit, lalu coba lagi.',
            ])));

        if (config('trustedproxy.proxies') === '*') {
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
            );
        }
    }
}
