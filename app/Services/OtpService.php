<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Check if the given phone is in the configured test phone whitelist.
     */
    public function isTestPhone(string $phone): bool
    {
        $testPhones = config('otp.test_phones', ['7458086472', '9651017054', '7303838972', '8009210084']);
        return in_array($phone, $testPhones, true);
    }

    /**
     * Check if OTP generation is rate-limited (cooldown or window overflow).
     * Returns null if allowed, or structured array with 429 error if blocked.
     */
    public function checkSendRateLimit(string $phone): ?array
    {
        // 1. Check strict per-request cooldown
        $cooldownKey = "otp_cooldown:{$phone}";
        $cooldownLimit = (int) config('otp.cooldown_seconds', 30);

        if (Cache::has($cooldownKey)) {
            $lastSent = (int) Cache::get($cooldownKey);
            $diff = time() - $lastSent;
            if ($diff < $cooldownLimit) {
                $retryAfter = $cooldownLimit - $diff;
                return [
                    'status'              => 'error',
                    'message'             => "Please wait {$retryAfter} seconds before requesting a new OTP.",
                    'error_code'          => 'OTP_COOLDOWN_ACTIVE',
                    'retry_after_seconds' => $retryAfter,
                ];
            }
        }

        // 2. Window rate limit (e.g. max 4 OTPs per 10 minutes) - test phones bypass this
        if (!$this->isTestPhone($phone)) {
            $windowKey = "otp_count:{$phone}";
            $count = (int) Cache::get($windowKey, 0);
            $maxPerWindow = (int) config('otp.max_requests_per_window', 4);

            if ($count >= $maxPerWindow) {
                $windowMinutes = (int) config('otp.window_minutes', 10);
                return [
                    'status'              => 'error',
                    'message'             => "Too many OTP requests for this phone number. Please wait a few minutes.",
                    'error_code'          => 'OTP_COOLDOWN_ACTIVE',
                    'retry_after_seconds' => 60,
                ];
            }
        }

        return null;
    }

    /**
     * Generate 4-digit OTP (fixed for test numbers, random secure digits for real users).
     */
    public function generateOtp(string $phone): string
    {
        if ($this->isTestPhone($phone)) {
            return (string) config('otp.fixed_test_otp', '1234');
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get OTP expiry Carbon instance.
     */
    public function getExpiry(): Carbon
    {
        $minutes = (int) config('otp.expiry_minutes', 10);
        return Carbon::now()->addMinutes($minutes);
    }

    /**
     * Record OTP dispatch: sets cooldown, increments window counter, and clears prior failed verify attempts.
     */
    public function recordOtpSent(string $phone): void
    {
        $cooldown = (int) config('otp.cooldown_seconds', 30);
        $windowMinutes = (int) config('otp.window_minutes', 10);

        // 1. Set cooldown
        Cache::put("otp_cooldown:{$phone}", time(), $cooldown);

        // 2. Increment window count
        $windowKey = "otp_count:{$phone}";
        $count = (int) Cache::get($windowKey, 0);
        if ($count === 0) {
            Cache::put($windowKey, 1, Carbon::now()->addMinutes($windowMinutes));
        } else {
            Cache::increment($windowKey);
        }

        // 3. Reset failed attempts cache for new OTP cycle
        Cache::forget("otp_attempts:{$phone}");
    }

    /**
     * Safely dispatch SMS: real numbers send via Exotel queue, test numbers skip Exotel completely.
     */
    public function dispatchSms(string $phone, string $otp): void
    {
        if ($this->isTestPhone($phone)) {
            Log::info("OtpService: Test phone [{$phone}] detected. Skipped external Exotel SMS dispatch.");
            return;
        }

        \App\Jobs\SendSmsOtpJob::dispatch($phone, $otp);
    }

    /**
     * Check if user is locked out due to too many failed verify attempts.
     */
    public function checkVerifyAttempts(string $phone): ?array
    {
        $maxAttempts = (int) config('otp.max_verify_attempts', 5);
        $attempts = (int) Cache::get("otp_attempts:{$phone}", 0);

        if ($attempts >= $maxAttempts) {
            $lockoutSeconds = ((int) config('otp.lockout_minutes', 10)) * 60;
            return [
                'status'              => 'error',
                'message'             => 'Too many invalid OTP attempts. Please wait 10 minutes or request a new OTP.',
                'error_code'          => 'MAX_OTP_ATTEMPTS_EXCEEDED',
                'retry_after_seconds' => $lockoutSeconds,
            ];
        }

        return null;
    }

    /**
     * Validate OTP correctness considering test numbers and expiry.
     */
    public function isOtpValid(?string $savedOtp, mixed $expiresAt, string $inputOtp, string $phone): bool
    {
        // Test user bypass
        if ($this->isTestPhone($phone) && $inputOtp === (string) config('otp.fixed_test_otp', '1234')) {
            return true;
        }

        if (empty($savedOtp) || empty($expiresAt)) {
            return false;
        }

        $expiry = $expiresAt instanceof Carbon ? $expiresAt : Carbon::parse($expiresAt);
        if (Carbon::now()->gt($expiry)) {
            return false;
        }

        return hash_equals((string) $savedOtp, (string) $inputOtp);
    }

    /**
     * Record a failed verification attempt.
     */
    public function recordFailedAttempt(string $phone): array
    {
        $attemptKey = "otp_attempts:{$phone}";
        $lockoutMinutes = (int) config('otp.lockout_minutes', 10);
        $maxAttempts = (int) config('otp.max_verify_attempts', 5);

        $attempts = (int) Cache::increment($attemptKey);
        if ($attempts === 1) {
            Cache::put($attemptKey, 1, Carbon::now()->addMinutes($lockoutMinutes));
        }

        $remaining = max(0, $maxAttempts - $attempts);

        return [
            'status'             => 'error',
            'message'            => "Invalid OTP. You have {$remaining} attempt(s) remaining.",
            'error_code'         => 'INVALID_OTP',
            'remaining_attempts' => $remaining,
        ];
    }

    /**
     * Clear OTP state after successful verification.
     */
    public function clearState(string $phone): void
    {
        Cache::forget("otp_attempts:{$phone}");
        Cache::forget("otp_cooldown:{$phone}");
    }
}
