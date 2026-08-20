<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

class NormalCallLifecycleTest extends TestCase
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
            'name' => 'Jane Consumer',
            'user_type' => 'user',
        ]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        $this->provider = User::factory()->create([
            'name' => 'Astro Call Guru',
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

    public function test_normal_call_initiation_acceptance_ice_relay_and_completion()
    {
        // 1. Consumer initiates call with SDP offer
        Sanctum::actingAs($this->consumer);
        $initiateResponse = $this->postJson('/api/v1/call/initiate', [
            'provider_id' => $this->provider->id,
            'offer' => 'v=0\r\no=alice...',
        ]);

        $initiateResponse->assertStatus(200);
        $sessionId = $initiateResponse->json('data.session.id');
        $this->assertNotNull($sessionId);
        $this->assertEquals('initiated', $initiateResponse->json('data.session.status'));

        // 2. Astrologer accepts call with SDP answer
        Sanctum::actingAs($this->provider);
        $acceptResponse = $this->postJson("/api/v1/call/{$sessionId}/accept", [
            'answer' => 'v=0\r\no=bob...',
        ]);

        $acceptResponse->assertStatus(200);
        $this->assertEquals('ongoing', $acceptResponse->json('data.session.status'));

        // 3. ICE Candidate relay
        Sanctum::actingAs($this->consumer);
        $iceResponse = $this->postJson("/api/v1/call/{$sessionId}/ice-candidate", [
            'candidate' => 'candidate:1 1 UDP 2130706431 192.168.1.1 5000 typ host',
        ]);
        $iceResponse->assertStatus(200);

        // 4. End Call
        $endResponse = $this->postJson("/api/v1/call/{$sessionId}/end");
        $endResponse->assertStatus(200);
        $this->assertEquals('completed', $endResponse->json('data.session.status'));
    }

    public function test_normal_call_rejection_and_cancellation()
    {
        // 1. Initiate call
        Sanctum::actingAs($this->consumer);
        $initiateResponse = $this->postJson('/api/v1/call/initiate', [
            'provider_id' => $this->provider->id,
            'offer' => 'v=0\r\no=alice...',
        ]);
        $sessionId = $initiateResponse->json('data.session.id');

        // 2. Consumer cancels
        $cancelResponse = $this->postJson("/api/v1/call/{$sessionId}/cancel");
        $cancelResponse->assertStatus(200);
        $this->assertEquals('missed', CallSession::find($sessionId)->status);

        // 3. Initiate second call and Astrologer rejects
        $initiateResponse2 = $this->postJson('/api/v1/call/initiate', [
            'provider_id' => $this->provider->id,
            'offer' => 'v=0\r\no=alice2...',
        ]);
        $sessionId2 = $initiateResponse2->json('data.session.id');

        Sanctum::actingAs($this->provider);
        $rejectResponse = $this->postJson("/api/v1/call/{$sessionId2}/reject");
        $rejectResponse->assertStatus(200);
        $this->assertEquals('rejected', CallSession::find($sessionId2)->status);
    }
}
