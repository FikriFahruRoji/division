<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Global web rate limit - 100 requests per minute per IP
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip())
                ->response(function () {
                    return response()->view('errors.429', [
                        'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.',
                    ], 429);
                });
        });

        // Strict limit for login attempts - 5 per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.',
                    ]);
                });
        });

        // Limit for document uploads - 10 per minute
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Limit for signature actions - 20 per minute
        RateLimiter::for('signatures', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Limit for public verification page - protect from scraping
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Strict limit for API requests - 60 per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Very strict limit for password reset - 3 per hour
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    }
}
