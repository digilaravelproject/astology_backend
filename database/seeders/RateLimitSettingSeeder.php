<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class RateLimitSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set(
            key: 'rate_limit_enabled',
            value: true,
            type: 'boolean',
            group: 'rate_limit',
            description: 'Master toggle to enable/disable ALL rate limiting globally'
        );

        Setting::set(
            key: 'rate_limit_otp',
            value: 5,
            type: 'integer',
            group: 'rate_limit',
            description: 'OTP endpoints (send/verify/resend) - attempts per minute per IP'
        );

        Setting::set(
            key: 'rate_limit_auth',
            value: 60,
            type: 'integer',
            group: 'rate_limit',
            description: 'Authentication endpoints (signup) - attempts per minute per IP'
        );

        Setting::set(
            key: 'rate_limit_general',
            value: 60,
            type: 'integer',
            group: 'rate_limit',
            description: 'General public API endpoints - requests per minute per user/IP'
        );

        Setting::set(
            key: 'rate_limit_tiered',
            value: 30,
            type: 'integer',
            group: 'rate_limit',
            description: 'Tiered/mutation endpoints (wallet, reviews, comments) - requests per minute per user/IP'
        );

        Setting::set(
            key: 'rate_limit_live_watch',
            value: 100,
            type: 'integer',
            group: 'rate_limit',
            description: 'Live watch endpoints - requests per minute per user/IP'
        );

        Setting::set(
            key: 'rate_limit_api',
            value: 120,
            type: 'integer',
            group: 'rate_limit',
            description: 'General API endpoints - requests per minute per user/IP'
        );
    }
}