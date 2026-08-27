<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define rate limiters for the application.
     */
    public function boot(): void
    {
        // 1. Smart OTP Rate Limiter (Throttles per Target Phone Number + Device Fingerprint)
        RateLimiter::for('otp', function (Request $request) {
            if (! Setting::get('rate_limit_enabled', true)) {
                return Limit::none();
            }

            $deviceKey = $this->resolveDeviceFingerprint($request);
            $phone = preg_replace('/[^0-9]/', '', (string) ($request->input('phone') ?? $request->input('mobile') ?? $request->input('phone_number') ?? ''));

            $limits = [
                Limit::perMinute((int) Setting::get('rate_limit_otp_device', 20))
                    ->by("otp_device:{$deviceKey}")
                    ->response($this->rateLimitResponse('Too many OTP attempts from this device. Please wait a moment.')),
            ];

            if (! empty($phone)) {
                $limits[] = Limit::perMinute((int) Setting::get('rate_limit_otp_phone', 10))
                    ->by("otp_phone:{$phone}")
                    ->response($this->rateLimitResponse('Too many OTP requests for this phone number. Please wait a moment.'));
            }

            return $limits;
        });

        // 2. Auth Endpoints (Login / Signup)
        RateLimiter::for('auth', function (Request $request) {
            return $this->dynamicLimit('auth', 120, $request);
        });

        // 3. General Public Content
        RateLimiter::for('general', function (Request $request) {
            return $this->dynamicLimit('general', 180, $request);
        });

        // 4. Authenticated Tiered User/Astrologer Endpoints
        RateLimiter::for('tiered', function (Request $request) {
            return $this->dynamicLimit('tiered', 180, $request);
        });

        // 5. High-Frequency Live Streaming & Chat Watchers
        RateLimiter::for('live_watch', function (Request $request) {
            return $this->dynamicLimit('live_watch', 300, $request);
        });

        // 6. Global Fallback API Rate Limiter
        RateLimiter::for('api', function (Request $request) {
            return $this->dynamicLimit('api', 300, $request);
        });
    }

    /**
     * Resolve a unique identity using User ID, Mobile Device Headers, or Smart Fingerprint.
     */
    private function resolveDeviceFingerprint(Request $request): string
    {
        // 1. Authenticated user has the highest priority
        if ($user = $request->user()) {
            return 'user:' . $user->id;
        }

        // 2. Mobile App Device ID header (if passed by Flutter / React Native / Native apps)
        $deviceId = $request->header('X-Device-Id') 
            ?? $request->header('X-Device-UUID') 
            ?? $request->header('Device-Id') 
            ?? $request->header('X-App-Device-Id');

        if ($deviceId) {
            return 'device:' . substr(hash('sha256', $deviceId), 0, 32);
        }

        // 3. Smart Composite Fingerprint (IP + User-Agent + Accept-Language + Platform)
        // Differentiates multiple phones sharing the same Wi-Fi / CGNAT mobile tower IP
        $rawFingerprint = implode('|', [
            $request->ip() ?? '0.0.0.0',
            $request->userAgent() ?? 'generic-ua',
            $request->header('Accept-Language') ?? 'en',
            $request->header('Sec-CH-UA-Platform') ?? 'unknown-os',
        ]);

        return 'fp:' . hash('sha256', $rawFingerprint);
    }

    /**
     * Build dynamic rate limit with custom JSON response.
     */
    private function dynamicLimit(string $name, int $default, Request $request): Limit
    {
        if (! Setting::get('rate_limit_enabled', true)) {
            return Limit::none();
        }

        $attempts = (int) Setting::get("rate_limit_{$name}", $default);
        $key = $this->resolveDeviceFingerprint($request);

        return Limit::perMinute($attempts)
            ->by("{$name}:{$key}")
            ->response($this->rateLimitResponse("Rate limit exceeded for {$name}. Please try again shortly."));
    }

    /**
     * Standardized JSON response for 429 Too Many Requests.
     */
    private function rateLimitResponse(string $message): \Closure
    {
        return function (Request $request, array $headers) use ($message) {
            $retryAfter = $headers['Retry-After'] ?? 60;

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after_seconds' => (int) $retryAfter,
            ], 429, $headers);
        };
    }
}
