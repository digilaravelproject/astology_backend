<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration & Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Centralized settings for OTP generation, test numbers, and rate limits.
    | Prevents telecom operator (Jio/Airtel/Vi) spam drops and preserves
    | 100% backward compatibility with mobile APKs.
    |
    */

    // Test numbers with bypass (no external SMS dispatched, fixed OTP)
    'test_phones' => array_filter(
        array_map('trim', explode(',', env('OTP_TEST_PHONES', '7458086472,9651017054,7303838972,8009210084')))
    ),

    // Fixed OTP for test phones
    'fixed_test_otp' => env('OTP_FIXED_TEST_CODE', '1234'),

    // Per-request resend cooldown (seconds) - APK uses dynamic retry_after_seconds
    'cooldown_seconds' => (int) env('OTP_COOLDOWN_SECONDS', 30),

    // Operator-safe window: max OTPs allowed in a sliding window (default: 4 OTPs per 10 minutes)
    'max_requests_per_window' => (int) env('OTP_MAX_REQUESTS_WINDOW', 4),
    'window_minutes' => (int) env('OTP_WINDOW_MINUTES', 10),

    // Verification protection
    'max_verify_attempts' => (int) env('OTP_MAX_VERIFY_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('OTP_LOCKOUT_MINUTES', 10),

    // OTP Expiry
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
];
