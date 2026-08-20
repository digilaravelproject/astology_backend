<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\Message;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

class ChatHistoryAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected $consumer;
    protected $provider;
    protected $astrologer;
    protected $chatSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = User::factory()->create([
            'user_type' => 'user',
        ]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        $this->provider = User::factory()->create([
            'user_type' => 'astrologer',
        ]);
        Wallet::create(['user_id' => $this->provider->id, 'balance' => 0.00]);

        $this->astrologer = Astrologer::create([
            'user_id' => $this->provider->id,
            'is_chat_enabled' => true,
            'is_call_enabled' => true,
            'chat_rate_per_minute' => 20.00,
            'call_rate_per_minute' => 25.00,
            'is_active' => true,
        ]);

        $this->chatSession = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 20.00,
            'started_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    public function test_page_1_returns_most_recent_messages_in_chronological_display_order()
    {
        // Seed 40 messages created at 1-minute intervals
        for ($i = 1; $i <= 40; $i++) {
            Message::create([
                'chat_session_id' => $this->chatSession->id,
                'sender_id' => ($i % 2 === 0) ? $this->consumer->id : $this->provider->id,
                'receiver_id' => ($i % 2 === 0) ? $this->provider->id : $this->consumer->id,
                'message' => "Message number {$i}",
                'type' => 'text',
                'is_read' => true,
                'created_at' => now()->subMinutes(50 - $i),
            ]);
        }

        Sanctum::actingAs($this->consumer);

        $response = $this->getJson("/api/v1/chat/{$this->chatSession->id}/messages?per_page=30&page=1");

        $response->assertStatus(200);
        $data = $response->json('data.data');

        // Page 1 with per_page=30 on 40 items must return exactly 30 messages
        $this->assertCount(30, $data);

        // The very first message on Page 1 must be message 11, and the last must be message 40 (most recent!)
        $this->assertEquals("Message number 11", $data[0]['message']);
        $this->assertEquals("Message number 40", $data[29]['message']);

        // Check Page 2 (oldest 10 messages)
        $responsePage2 = $this->getJson("/api/v1/chat/{$this->chatSession->id}/messages?per_page=30&page=2");
        $responsePage2->assertStatus(200);
        $dataPage2 = $responsePage2->json('data.data');

        $this->assertCount(10, $dataPage2);
        $this->assertEquals("Message number 1", $dataPage2[0]['message']);
        $this->assertEquals("Message number 10", $dataPage2[9]['message']);
    }

    public function test_unauthorized_user_cannot_access_chat_history()
    {
        $intruder = User::factory()->create(['user_type' => 'user']);
        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/v1/chat/{$this->chatSession->id}/messages");

        $response->assertStatus(403);
    }
}
