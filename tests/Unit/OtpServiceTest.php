<?php

namespace Tests\Unit;

use App\Jobs\SendSmsOtpJob;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    protected OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = new OtpService();
        Cache::flush();
    }

    public function test_identifies_test_phones_correctly(): void
    {
        $this->assertTrue($this->otpService->isTestPhone('7458086472'));
        $this->assertTrue($this->otpService->isTestPhone('7303838972'));
        $this->assertFalse($this->otpService->isTestPhone('9876543210'));
    }

    public function test_generates_fixed_otp_for_test_phones(): void
    {
        $otp = $this->otpService->generateOtp('7458086472');
        $this->assertSame('1234', $otp);
    }

    public function test_generates_random_4_digit_otp_for_real_phones(): void
    {
        $otp = $this->otpService->generateOtp('9876543210');
        $this->assertSame(4, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $otp);
    }

    public function test_cooldown_rate_limits_subsequent_send_requests(): void
    {
        $phone = '9876543210';

        // 1. Initial check: should be null (allowed)
        $this->assertNull($this->otpService->checkSendRateLimit($phone));

        // 2. Mark OTP as sent
        $this->otpService->recordOtpSent($phone);

        // 3. Immediately checking again should return cooldown error (HTTP 429 payload)
        $rateLimit = $this->otpService->checkSendRateLimit($phone);
        $this->assertNotNull($rateLimit);
        $this->assertSame('error', $rateLimit['status']);
        $this->assertSame('OTP_COOLDOWN_ACTIVE', $rateLimit['error_code']);
        $this->assertGreaterThan(0, $rateLimit['retry_after_seconds']);
    }

    public function test_window_rate_limit_blocks_excessive_otp_requests(): void
    {
        $phone = '9876543210';
        $max = (int) config('otp.max_requests_per_window', 4);

        // Manually set window counter to max limit
        Cache::put("otp_count:{$phone}", $max, 600);

        $result = $this->otpService->checkSendRateLimit($phone);
        $this->assertNotNull($result);
        $this->assertSame('error', $result['status']);
        $this->assertSame('OTP_COOLDOWN_ACTIVE', $result['error_code']);
    }

    public function test_validates_otp_correctly(): void
    {
        $realPhone = '9876543210';
        $testPhone = '7458086472';
        $future = Carbon::now()->addMinutes(10);
        $past = Carbon::now()->subMinute();

        // 1. Test phone with '1234' is always valid
        $this->assertTrue($this->otpService->isOtpValid('9999', $past, '1234', $testPhone));

        // 2. Real phone with correct OTP before expiry is valid
        $this->assertTrue($this->otpService->isOtpValid('5678', $future, '5678', $realPhone));

        // 3. Real phone with wrong OTP is invalid
        $this->assertFalse($this->otpService->isOtpValid('5678', $future, '0000', $realPhone));

        // 4. Real phone with expired OTP is invalid
        $this->assertFalse($this->otpService->isOtpValid('5678', $past, '5678', $realPhone));
    }

    public function test_failed_attempts_lockout_after_max_attempts(): void
    {
        $phone = '9876543210';

        // 1 to 4 failed attempts
        for ($i = 1; $i <= 4; $i++) {
            $this->assertNull($this->otpService->checkVerifyAttempts($phone));
            $failed = $this->otpService->recordFailedAttempt($phone);
            $this->assertSame(5 - $i, $failed['remaining_attempts']);
        }

        // 5th failed attempt
        $failed = $this->otpService->recordFailedAttempt($phone);
        $this->assertSame(0, $failed['remaining_attempts']);

        // Now checkVerifyAttempts must return lockout error
        $lockout = $this->otpService->checkVerifyAttempts($phone);
        $this->assertNotNull($lockout);
        $this->assertSame('MAX_OTP_ATTEMPTS_EXCEEDED', $lockout['error_code']);
        $this->assertSame(600, $lockout['retry_after_seconds']);
    }

    public function test_clear_state_resets_attempts_and_cooldown(): void
    {
        $phone = '9876543210';

        $this->otpService->recordOtpSent($phone);
        $this->otpService->recordFailedAttempt($phone);

        $this->assertTrue(Cache::has("otp_cooldown:{$phone}"));
        $this->assertTrue(Cache::has("otp_attempts:{$phone}"));

        $this->otpService->clearState($phone);

        $this->assertFalse(Cache::has("otp_cooldown:{$phone}"));
        $this->assertFalse(Cache::has("otp_attempts:{$phone}"));
    }

    public function test_dispatch_sms_skips_test_phones(): void
    {
        Queue::fake();

        // Test phone should NOT dispatch job
        $this->otpService->dispatchSms('7458086472', '1234');
        Queue::assertNotPushed(SendSmsOtpJob::class);

        // Real phone MUST dispatch job
        $this->otpService->dispatchSms('9876543210', '5678');
        Queue::assertPushed(SendSmsOtpJob::class, function ($job) {
            return $job->phone === '9876543210' && $job->otp === '5678';
        });
    }
}
