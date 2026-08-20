<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

class NormalChatLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $consumer;
    protected $provider;
    protected $astrologer;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();

        $this->consumer = User::factory()->create([
            'name' => 'John Doe',
            'user_type' => 'user',
        ]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        $this->provider = User::factory()->create([
            'name' => 'Astro Pandit',
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
    }

    public function test_normal_chat_initiation_acceptance_and_completion()
    {
        // 1. Consumer initiates chat
        Sanctum::actingAs($this->consumer);
        $initiateResponse = $this->postJson('/api/v1/chat/initiate', [
            'provider_id' => $this->provider->id,
            'question' => 'Career advice',
        ]);

        $initiateResponse->assertStatus(200);
        $sessionId = $initiateResponse->json('data.session.id');
        $this->assertNotNull($sessionId);
        $this->assertEquals('initiated', $initiateResponse->json('data.session.status'));

        // 2. Astrologer accepts chat
        Sanctum::actingAs($this->provider);
        $acceptResponse = $this->postJson("/api/v1/chat/{$sessionId}/accept");

        $acceptResponse->assertStatus(200);
        $this->assertEquals('ongoing', $acceptResponse->json('data.session.status'));

        // 3. Send message
        $msgResponse = $this->postJson("/api/v1/chat/{$sessionId}/message", [
            'message' => 'Hello Pandit Ji',
            'type' => 'text',
        ]);
        $msgResponse->assertStatus(200);

        // 4. End chat
        Sanctum::actingAs($this->consumer);
        $endResponse = $this->postJson("/api/v1/chat/{$sessionId}/end");

        $endResponse->assertStatus(200);
        $this->assertEquals('completed', $endResponse->json('data.session.status'));
    }

    public function test_normal_chat_rejection_and_cancellation()
    {
        // 1. Initiate chat
        Sanctum::actingAs($this->consumer);
        $initiateResponse = $this->postJson('/api/v1/chat/initiate', [
            'provider_id' => $this->provider->id,
            'question' => 'Love life advice',
        ]);
        $sessionId = $initiateResponse->json('data.session.id');

        // 2. Consumer cancels
        $cancelResponse = $this->postJson("/api/v1/chat/{$sessionId}/cancel");
        $cancelResponse->assertStatus(200);
        $this->assertEquals('cancelled', ChatSession::find($sessionId)->status);

        // 3. Initiate second chat and Astrologer rejects
        $initiateResponse2 = $this->postJson('/api/v1/chat/initiate', [
            'provider_id' => $this->provider->id,
            'question' => 'Health advice',
        ]);
        $sessionId2 = $initiateResponse2->json('data.session.id');

        Sanctum::actingAs($this->provider);
        $rejectResponse = $this->postJson("/api/v1/chat/{$sessionId2}/reject");
        $rejectResponse->assertStatus(200);
        $this->assertEquals('rejected', ChatSession::find($sessionId2)->status);
    }
}
