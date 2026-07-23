<?php

namespace Tests\Feature;

use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AstrologerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/astrologer/performance');
        $response->assertStatus(401);
    }

    public function test_returns_correct_performance_metrics(): void
    {
        // 1. Create User and Astrologer Profile
        $user = User::factory()->create();
        $astrologer = Astrologer::factory()->create([
            'user_id' => $user->id,
            'call_rate_per_minute' => 50.00,
            'chat_rate_per_minute' => 30.00,
            'availability' => [
                [
                    'day' => strtolower(now()->format('l')),
                    'enabled' => true,
                    'slots' => [
                        ['start' => '09:00', 'end' => '11:00'], // 120 mins
                        ['start' => '14:00', 'end' => '17:00'], // 180 mins
                    ]
                ]
            ]
        ]);

        // 2. Create another user (Consumer)
        $consumer = User::factory()->create();

        // 3. Create completed sessions for today (to test busy minutes)
        // 30 mins call
        CallSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $user->id,
            'status' => 'completed',
            'duration_seconds' => 1800,
            'rate_per_minute' => 50.00,
            'total_cost' => 1500.00,
            'started_at' => now(),
            'ended_at' => now()->addMinutes(30),
            'created_at' => now(),
        ]);

        // 10 mins chat
        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $user->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'rate_per_minute' => 30.00,
            'total_cost' => 300.00,
            'started_at' => now(),
            'ended_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // 4. Create missed/failed/rejected sessions for today
        CallSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $user->id,
            'status' => 'missed',
            'duration_seconds' => 0,
            'rate_per_minute' => 50.00,
            'total_cost' => 0.00,
            'created_at' => now(),
        ]);

        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $user->id,
            'status' => 'rejected',
            'duration_seconds' => 0,
            'rate_per_minute' => 30.00,
            'total_cost' => 0.00,
            'created_at' => now(),
        ]);

        // Request performance endpoint
        $response = $this->actingAs($user)->getJson('/api/v1/astrologer/performance');

        // Assert response status
        $response->assertStatus(200);

        // Verify JSON response structure and values
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'badge_type',
                'profile_health' => [
                    'date',
                    'total_sessions',
                    'missed_sessions',
                    'revenue_loss',
                    'missed_calls',
                    'missed_chats',
                    'loyal_users',
                ],
                'availability' => [
                    'available_mins' => [
                        'today',
                        'seven_days',
                        'thirty_days',
                    ],
                    'busy_mins' => [
                        'today',
                        'seven_days',
                        'thirty_days',
                    ]
                ],
                'loyal_user_conversion' => [
                    'conversion_percentage',
                    'total_users',
                    'loyal_users',
                    'loyal_user_level',
                ]
            ]
        ]);

        $data = $response->json('data');

        // Check values
        $this->assertEquals('Rising Star', $data['badge_type']);
        $this->assertEquals(4, $data['profile_health']['total_sessions']); // 1 comp call + 1 comp chat + 1 missed call + 1 rejected chat
        $this->assertEquals(2, $data['profile_health']['missed_sessions']); // 1 missed call + 1 rejected chat
        $this->assertEquals(400, $data['profile_health']['revenue_loss']); // (1 call * 50 * 5) + (1 chat * 30 * 5) = 250 + 150 = 400
        $this->assertEquals(1, $data['profile_health']['missed_calls']);
        $this->assertEquals(1, $data['profile_health']['missed_chats']);

        // Availability calculations
        $this->assertEquals(300, $data['availability']['available_mins']['today']); // 120 + 180 = 300
        $this->assertEquals(40, $data['availability']['busy_mins']['today']); // 30 + 10 = 40 mins
    }
}
