<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ChatAssistanceSession;
use App\Models\ChatAssistanceAstrologerLimit;
use App\Models\Setting;

class ChatAssistanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('chat_assistance_enabled', true);
        Setting::set('chat_assistance_daily_limit', 5);
    }



    public function test_astrologer_daily_reply_limits()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $astrologer = User::factory()->create(['user_type' => 'astrologer']);

        $session = ChatAssistanceSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $astrologer->id,
        ]);

        // Send 5 messages successfully (daily limit is 5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($astrologer)->postJson("/api/v1/chat-assistance/{$session->id}/message", [
                'message' => "Reply message {$i}",
            ]);
            $response->assertStatus(200);
        }

        // The 6th message should fail because the daily limit is exhausted
        $response = $this->actingAs($astrologer)->postJson("/api/v1/chat-assistance/{$session->id}/message", [
            'message' => 'Should fail',
        ]);
        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
    }
}
