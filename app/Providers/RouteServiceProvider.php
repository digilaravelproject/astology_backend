<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define rate limiters for the application.
     */
    public function boot(): void
    {
        // OTP endpoints: 5 attempts per minute per IP
        RateLimiter::for('otp', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Authentication endpoints: 10 attempts per minute per IP
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // General API endpoints: 60 requests per minute per user/IP
        RateLimiter::for('general', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(60)->by($key);
        });

        // Tiered/mutation endpoints (wallet, reviews, etc.): 30 requests per minute
        RateLimiter::for('tiered', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(30)->by($key);
        });
    }
}
