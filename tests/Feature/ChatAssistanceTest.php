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

    public function test_initiate_reuses_existing_session()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $astrologer = User::factory()->create(['user_type' => 'astrologer']);

        // 1. Initiate from consumer first
        $response1 = $this->actingAs($consumer)->postJson('/api/v1/chat-assistance/initiate', [
            'provider_id' => $astrologer->id,
        ]);
        $response1->assertStatus(200);
        $sessionId1 = $response1->json('data.session.id');

        // 2. Initiate from consumer again
        $response2 = $this->actingAs($consumer)->postJson('/api/v1/chat-assistance/initiate', [
            'provider_id' => $astrologer->id,
        ]);
        $response2->assertStatus(200);
        $sessionId2 = $response2->json('data.session.id');
        $this->assertEquals($sessionId1, $sessionId2);

        // 3. Initiate from astrologer to consumer
        $response3 = $this->actingAs($astrologer)->postJson('/api/v1/chat-assistance/initiate', [
            'provider_id' => $consumer->id,
        ]);
        $response3->assertStatus(200);
        $sessionId3 = $response3->json('data.session.id');
        $this->assertEquals($sessionId1, $sessionId3);
    }

    public function test_get_sessions_returns_formatted_profile_photos()
    {
        $consumer = User::factory()->create([
            'user_type' => 'user',
            'profile_photo' => 'users/1/profile.png'
        ]);
        $astrologer = User::factory()->create([
            'user_type' => 'astrologer',
            'profile_photo' => 'astrologers/2/profile.png'
        ]);

        ChatAssistanceSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $astrologer->id,
        ]);

        $response = $this->actingAs($consumer)->getJson('/api/v1/chat-assistance/sessions');

        $response->assertStatus(200);
        $sessions = $response->json('data.data');
        $this->assertCount(1, $sessions);

        $consumerPhoto = $sessions[0]['consumer']['profile_photo'];
        $providerPhoto = $sessions[0]['provider']['profile_photo'];

        $this->assertEquals('users/1/profile.png', $consumerPhoto);
        $this->assertEquals('astrologers/2/profile.png', $providerPhoto);
    }

    public function test_chat_assistance_events_dispatch_with_correct_payloads()
    {
        \Illuminate\Support\Facades\Event::fake();

        $consumer = User::factory()->create(['user_type' => 'user']);
        $astrologer = User::factory()->create(['user_type' => 'astrologer']);

        // Test ChatAssistanceInitiated
        $response = $this->actingAs($consumer)->postJson('/api/v1/chat-assistance/initiate', [
            'provider_id' => $astrologer->id,
        ]);
        $response->assertStatus(200);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\ChatAssistanceInitiated::class, function ($event) use ($consumer) {
            $payload = $event->broadcastWith();
            return isset($payload['session']) && $payload['senderData']['id'] === $consumer->id;
        });

        $sessionId = $response->json('data.session.id');

        // Test ChatAssistanceMessageSent
        $responseMessage = $this->actingAs($consumer)->postJson("/api/v1/chat-assistance/{$sessionId}/message", [
            'message' => 'Hello',
        ]);
        $responseMessage->assertStatus(200);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\ChatAssistanceMessageSent::class, function ($event) use ($astrologer) {
            $payload = $event->broadcastWith();
            return $payload['messageData']['message'] === 'Hello' && $payload['receiverId'] === $astrologer->id;
        });
    }
}
