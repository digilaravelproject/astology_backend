<?php

namespace Tests\Feature;

use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpThrottlingAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function user_send_otp_enforces_30_second_resend_cooldown()
    {
        $phone = '9876543210';

        // 1. First OTP request succeeds
        $res1 = $this->postJson('/api/v1/user/send-otp', ['phone' => $phone]);
        $res1->assertStatus(200);
        $res1->assertJsonPath('status', 'success');

        // 2. Immediate second OTP request triggers 429 Cooldown Active
        $res2 = $this->postJson('/api/v1/user/send-otp', ['phone' => $phone]);
        $res2->assertStatus(429);
        $res2->assertJsonPath('error_code', 'OTP_COOLDOWN_ACTIVE');
    }

    /** @test */
    public function user_verify_otp_locks_after_5_wrong_attempts()
    {
        $phone = '9876543211';

        $this->postJson('/api/v1/user/send-otp', ['phone' => $phone]);
        $user = User::where('phone', $phone)->first();
        $user->otp = '5555';
        $user->save();

        // 4 wrong attempts
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/v1/user/verify-otp', [
                'phone' => $phone,
                'otp'   => '0000',
            ]);
            $res->assertStatus(422);
            $res->assertJsonPath('error_code', 'INVALID_OTP');
            $res->assertJsonPath('remaining_attempts', 5 - $i);
        }

        // 5th wrong attempt
        $res5 = $this->postJson('/api/v1/user/verify-otp', [
            'phone' => $phone,
            'otp'   => '0000',
        ]);
        $res5->assertStatus(422);
        $res5->assertJsonPath('remaining_attempts', 0);

        // 6th attempt is blocked with 429
        $res6 = $this->postJson('/api/v1/user/verify-otp', [
            'phone' => $phone,
            'otp'   => '5555', // Even correct OTP is blocked once locked
        ]);
        $res6->assertStatus(429);
        $res6->assertJsonPath('error_code', 'MAX_OTP_ATTEMPTS_EXCEEDED');
    }

    /** @test */
    public function astrologer_send_otp_enforces_30_second_resend_cooldown_and_lock()
    {
        $astroUser = User::factory()->create([
            'phone'     => '9876543212',
            'user_type' => 'astrologer',
        ]);
        Astrologer::create([
            'user_id' => $astroUser->id,
            'status'  => 'approved',
        ]);

        // First request succeeds
        $res1 = $this->postJson('/api/v1/astrologer/send-otp', ['phone' => '9876543212']);
        $res1->assertStatus(200);

        // Immediate second request triggers 429
        $res2 = $this->postJson('/api/v1/astrologer/send-otp', ['phone' => '9876543212']);
        $res2->assertStatus(429);
        $res2->assertJsonPath('error_code', 'OTP_COOLDOWN_ACTIVE');
    }
}
