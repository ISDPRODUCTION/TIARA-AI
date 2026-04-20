<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        RateLimiter::for('chat', function (Request $request) {
            $limit = config('ai.rate_limit', 30);
            return Limit::perMinute($limit)->by(
                $request->ip()
            )->response(function () {
                return response()->json([
                    'error' => 'Terlalu banyak permintaan. Silakan tunggu sebentar sebelum mengirim pesan lagi.'
                ], 429);
            });
        });
    }
}
