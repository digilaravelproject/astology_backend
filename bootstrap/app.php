<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null; // This will trigger a 100% 401 JSON response instead of a redirect
            }
            return '/login'; // Fallback for web (though we don't have login defined)
        });
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'astrologer' => \App\Http\Middleware\AstrologerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();

/*
|--------------------------------------------------------------------------
| Rate Limiters
|--------------------------------------------------------------------------
|
| Register rate limiters for the application. These rates are applied
| to API routes to prevent abuse and brute force attacks.
|
*/
RateLimiter::for('otp', function ($request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('auth', function ($request) {
    return Limit::perMinute(10)->by($request->ip());
});

RateLimiter::for('general', function ($request) {
    $key = $request->user()?->id ?? $request->ip();
    return Limit::perMinute(60)->by($key);
});

RateLimiter::for('tiered', function ($request) {
    $key = $request->user()?->id ?? $request->ip();
    return Limit::perMinute(30)->by($key);
});

RateLimiter::for('live_watch', function ($request) {
    $key = $request->user()?->id ?? $request->ip();
    return Limit::perMinute(60)->by($key);
});

RateLimiter::for('api', function ($request) {
    $key = $request->user()?->id ?? $request->ip();
    return Limit::perMinute(120)->by($key);
});
