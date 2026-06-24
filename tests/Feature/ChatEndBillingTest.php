<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Events\ChatEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatEndBillingTest extends TestCase
{
    use RefreshDatabase;

    private $consumer;
    private $provider;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();

        // Lock time to a clean baseline to eliminate real-time test execution drift
        Carbon::setTestNow(Carbon::now());

        // 1. Create consumer with balance
        /** @var \App\Models\User $consumer */
        $this->consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $this->provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $this->provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);
        Wallet::create(['user_id' => $this->provider->id, 'balance' => 100.00]);

        // 3. Initiate Chat
        $response = $this->actingAs($this->consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $this->provider->id
        ]);
        $sessionId = $response->json('data.session.id');

        // 4. Astrologer accepts Chat
        $this->actingAs($this->provider)->postJson("/api/v1/chat/{$sessionId}/accept");

        $this->session = ChatSession::find($sessionId);
    }

    protected function tearDown(): void
    {
        // Reset Carbon mock time to prevent affecting subsequent tests
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function user_can_end_chat_with_correct_billing_calculations()
    {
        // Advance locked mock time by 5 minutes 10 seconds (310 seconds) -> ceil to 6 minutes
        Carbon::setTestNow(Carbon::now()->addMinutes(5)->addSeconds(10));

        // User ends the chat session
        $response = $this->actingAs($this->consumer)->postJson("/api/v1/chat/{$this->session->id}/end");

        $response->assertStatus(200);

        // Expected billing: 6 minutes * 15.00 = 90.00
        $expectedCost = 90;
        $expectedDuration = 310; // 5 minutes and 10 seconds = 310 seconds

        // Verify API response contains exact duration and amount details for both sides
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'session',
                'billing' => [
                    'duration_seconds',
                    'user_details' => [
                        'duration_seconds',
                        'amount_deducted'
                    ],
                    'astrologer_details' => [
                        'duration_seconds',
                        'amount_added'
                    ]
                ]
            ]
        ]);

        $response->assertJsonPath('data.billing.duration_seconds', $expectedDuration);
        $response->assertJsonPath('data.billing.user_details.amount_deducted', $expectedCost);
        $response->assertJsonPath('data.billing.astrologer_details.amount_added', 72); // 90 * 0.8 (20% commission fallback)

        // Verify database updates
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $this->session->id,
            'status' => 'completed',
            'total_cost' => $expectedCost
        ]);

        // Verify consumer wallet deducted
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->consumer->id,
            'balance' => 410.00 // 500.00 - 90.00
        ]);

        // Verify provider wallet credited with astrologer share (72.00)
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->provider->id,
            'balance' => 172.00 // 100.00 + 72.00
        ]);

        // Verify busy state reset to false
        $this->assertDatabaseHas('users', ['id' => $this->consumer->id, 'is_busy' => false]);
        $this->assertDatabaseHas('users', ['id' => $this->provider->id, 'is_busy' => false]);

        // Verify ChatEnded event was broadcasted with full billing details
        Event::assertDispatched(ChatEnded::class, function ($event) use ($expectedCost, $expectedDuration) {
            return (int) $event->session->id === (int) $this->session->id
                && (int) $event->billing['duration_seconds'] === $expectedDuration
                && (float) $event->billing['user_details']['amount_deducted'] === (float) $expectedCost
                && (float) $event->billing['astrologer_details']['amount_added'] === (float) $expectedCost;
        });
    }

    /** @test */
    public function astrologer_can_end_chat_with_correct_billing_calculations()
    {
        // Advance locked mock time by 3 minutes 20 seconds (200 seconds) -> ceil to 4 minutes
        Carbon::setTestNow(Carbon::now()->addMinutes(3)->addSeconds(20));

        // Astrologer ends the chat session
        $response = $this->actingAs($this->provider)->postJson("/api/v1/chat/{$this->session->id}/end");

        $response->assertStatus(200);

        // Expected billing: 4 minutes * 15.00 = 60.00
        $expectedCost = 60;
        $expectedDuration = 200; // 3 mins 20 seconds = 200 seconds

        // Verify API response
        $response->assertJsonPath('data.billing.duration_seconds', $expectedDuration);
        $response->assertJsonPath('data.billing.user_details.amount_deducted', $expectedCost);
        $response->assertJsonPath('data.billing.astrologer_details.amount_added', 48); // 60 * 0.8 (20% commission fallback)

        // Verify consumer wallet deducted
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->consumer->id,
            'balance' => 440.00 // 500 - 60
        ]);

        // Verify provider wallet credited
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->provider->id,
            'balance' => 148.00 // 100 + 48
        ]);

        // Verify busy state reset
        $this->assertDatabaseHas('users', ['id' => $this->consumer->id, 'is_busy' => false]);
        $this->assertDatabaseHas('users', ['id' => $this->provider->id, 'is_busy' => false]);
    }
}
