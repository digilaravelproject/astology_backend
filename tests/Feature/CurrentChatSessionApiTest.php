<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrentChatSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private $consumer;
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a consumer user
        $this->consumer = User::factory()->create([
            'name' => 'John Doe',
            'user_type' => 'user',
        ]);

        // Create an astrologer user
        $this->provider = User::factory()->create([
            'name' => 'Aacharya Sharma',
            'user_type' => 'astrologer',
        ]);
        
        // Ensure the astrologer has the related profile
        $this->provider->astrologer()->create([
            'chat_rate_per_minute' => 15.00,
            'is_online' => true,
        ]);
    }

    /** @test */
    public function it_returns_null_when_no_active_chat_session_exists()
    {
        // Consumer checks current chat session
        $response = $this->actingAs($this->consumer)->getJson('/api/v1/chat/sessions/current');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'success',
            'message' => 'No current chat session found',
            'data' => null,
        ]);

        // Astrologer checks current chat session
        $responseAstrologer = $this->actingAs($this->provider)->getJson('/api/v1/chat/sessions/current');

        $responseAstrologer->assertStatus(200);
        $responseAstrologer->assertJson([
            'success' => true,
            'status' => 'success',
            'message' => 'No current chat session found',
            'data' => null,
        ]);
    }

    /** @test */
    public function it_returns_the_session_details_for_both_consumer_and_provider_when_active()
    {
        $acceptedTime = now()->subMinutes(5);

        // Create active chat session
        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 15.00,
            'accepted_at' => $acceptedTime,
            'started_at' => $acceptedTime,
        ]);

        // Add an unread message from Provider to Consumer
        Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $this->provider->id,
            'receiver_id' => $this->consumer->id,
            'message' => 'Pranam! Tell me your birth details.',
            'type' => 'text',
            'is_read' => false,
        ]);

        // Consumer requests current session
        $response = $this->actingAs($this->consumer)->getJson('/api/v1/chat/sessions/current');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'status',
            'message',
            'data' => [
                'id',
                'consumer_id',
                'provider_id',
                'status',
                'rate_per_minute',
                'accepted_at',
                'unread_count',
                'provider' => [
                    'id',
                    'name',
                    'astrologer' => [
                        'chat_rate_per_minute',
                    ],
                ],
                'consumer' => [
                    'id',
                    'name',
                ],
                'latest_message' => [
                    'id',
                    'message',
                ],
            ],
        ]);

        $response->assertJsonPath('data.id', $session->id);
        $response->assertJsonPath('data.unread_count', 1);
        $response->assertJsonPath('data.provider.name', 'Aacharya Sharma');
        $response->assertJsonPath('data.consumer.name', 'John Doe');
        $response->assertJsonPath('data.latest_message.message', 'Pranam! Tell me your birth details.');

        // Provider requests current session
        $responseProvider = $this->actingAs($this->provider)->getJson('/api/v1/chat/sessions/current');

        $responseProvider->assertStatus(200);
        // For provider, unread count is 0 because the message was sent by them (not received)
        $responseProvider->assertJsonPath('data.unread_count', 0);
    }

    /** @test */
    public function it_returns_the_latest_session_if_multiple_active_sessions_exist()
    {
        $time1 = now()->subMinutes(10);
        $time2 = now()->subMinutes(2);

        // Session 1 (Older)
        $session1 = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 15.00,
            'accepted_at' => $time1,
            'created_at' => $time1,
        ]);

        // Session 2 (Newer)
        $session2 = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 15.00,
            'accepted_at' => $time2,
            'created_at' => $time2,
        ]);

        $response = $this->actingAs($this->consumer)->getJson('/api/v1/chat/sessions/current');

        $response->assertStatus(200);
        // Should return the newer session (session2)
        $response->assertJsonPath('data.id', $session2->id);
    }
}
