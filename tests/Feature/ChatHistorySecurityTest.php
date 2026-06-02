<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatHistorySecurityTest extends TestCase
{
    use RefreshDatabase;

    private $userA;
    private $userB;
    private $userC;
    private $sessionAB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create three users
        $this->userA = User::factory()->create(['name' => 'User A']);
        $this->userB = User::factory()->create(['name' => 'User B']);
        $this->userC = User::factory()->create(['name' => 'User C']);

        // Create a chat session between User A (consumer) and User B (provider)
        $this->sessionAB = ChatSession::create([
            'consumer_id' => $this->userA->id,
            'provider_id' => $this->userB->id,
            'status' => 'completed',
            'rate_per_minute' => 10.00,
            'duration_seconds' => 120,
            'total_cost' => 20.00,
        ]);

        // Add some messages to sessionAB
        Message::create([
            'chat_session_id' => $this->sessionAB->id,
            'sender_id' => $this->userA->id,
            'receiver_id' => $this->userB->id,
            'message' => 'Hello B, how are you?',
            'type' => 'text',
            'is_read' => true,
        ]);

        Message::create([
            'chat_session_id' => $this->sessionAB->id,
            'sender_id' => $this->userB->id,
            'receiver_id' => $this->userA->id,
            'message' => 'Hello A! I am fine.',
            'type' => 'text',
            'is_read' => false, // Unread message for User A
        ]);
    }

    /** @test */
    public function user_can_only_retrieve_their_own_consumer_sessions_with_latest_message_and_unread_count()
    {
        // Add another session where User A is provider (e.g. they acting as provider in another context, though rare, to verify segregation)
        $sessionCA = ChatSession::create([
            'consumer_id' => $this->userC->id,
            'provider_id' => $this->userA->id,
            'status' => 'completed',
        ]);

        // User A requests user sessions (where they are consumer)
        $response = $this->actingAs($this->userA)->getJson('/api/v1/chat/sessions/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'consumer_id',
                        'provider_id',
                        'latest_message',
                        'unread_count',
                    ]
                ]
            ]
        ]);

        // Should return only 1 session (sessionAB) because in sessionCA User A is provider, not consumer
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals($this->sessionAB->id, $response->json('data.data.0.id'));
        $this->assertEquals(1, $response->json('data.data.0.unread_count')); // 1 unread message from B to A
        $this->assertEquals('Hello A! I am fine.', $response->json('data.data.0.latest_message.message'));
    }

    /** @test */
    public function provider_can_only_retrieve_their_own_provider_sessions_with_latest_message_and_unread_count()
    {
        // User B requests astrologer sessions
        $response = $this->actingAs($this->userB)->getJson('/api/v1/chat/sessions/astrologer');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals($this->sessionAB->id, $response->json('data.data.0.id'));
        // For B, they sent the last message, so they have 0 unread messages in this session (since B is not the receiver of B's own message)
        $this->assertEquals(0, $response->json('data.data.0.unread_count'));
    }

    /** @test */
    public function participant_can_read_session_messages_detail()
    {
        // User A (consumer) can read
        $responseA = $this->actingAs($this->userA)->getJson("/api/v1/chat/{$this->sessionAB->id}/messages");
        $responseA->assertStatus(200);
        $this->assertCount(2, $responseA->json('data.data'));

        // User B (provider) can read
        $responseB = $this->actingAs($this->userB)->getJson("/api/v1/chat/{$this->sessionAB->id}/messages");
        $responseB->assertStatus(200);
        $this->assertCount(2, $responseB->json('data.data'));
    }

    /** @test */
    public function non_participant_cannot_read_session_messages_detail()
    {
        // User C (unauthorized third-party) attempts to read sessionAB messages
        $response = $this->actingAs($this->userC)->getJson("/api/v1/chat/{$this->sessionAB->id}/messages");
        
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You are not authorized to access this chat history.'
        ]);
    }
}
