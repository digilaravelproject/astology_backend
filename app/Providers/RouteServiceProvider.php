<?php

namespace App\Providers;

use App\Models\Setting;
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
        RateLimiter::for('otp', function ($request) {
            return $this->dynamicLimit('otp', 5, $request->by($request->ip());
        });

        RateLimiter::for('auth', function ($request) {
            return $this->dynamicLimit('auth', 60)->by($request->ip());
        });

        RateLimiter::for('general', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return $this->dynamicLimit('general', 60)->by($key);
        });

        RateLimiter::for('tiered', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return $this->dynamicLimit('tiered', 30)->by($key);
        });

        RateLimiter::for('live_watch', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return $this->dynamicLimit('live_watch', 100)->by($key);
        });

        RateLimiter::for('api', function ($request) {
            $key = $request->user()?->id ?? $request->ip();
            return $this->dynamicLimit('api', 120)->by($key);
        });
    }

    private function dynamicLimit(string $name, int $default): Limit
    {
        if (!Setting::get('rate_limit_enabled', true, 'boolean')) {
            return Limit::none();
        }

        $attempts = Setting::get("rate_limit_{$name}", $default, 'integer');

        return Limit::perMinute($attempts);
    }
}
