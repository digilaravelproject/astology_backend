<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Events\ChatDismissed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatDismissFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_cancel_initiated_chat_successfully()
    {
        Event::fake();
        Queue::fake();

        // 1. Create consumer with balance
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);

        // 3. Initiate Chat
        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id
        ]);
        $response->assertStatus(200);
        $sessionId = $response->json('data.session.id');

        // Verify session was initiated
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $sessionId,
            'status' => 'initiated'
        ]);

        // 4. Consumer cancels the initiated chat
        $cancelResponse = $this->actingAs($consumer)->postJson("/api/v1/chat/{$sessionId}/cancel");
        $cancelResponse->assertStatus(200);

        // Verify status transitioned to rejected and players are free/available
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $sessionId,
            'status' => 'rejected'
        ]);
        $this->assertDatabaseHas('users', ['id' => $consumer->id, 'is_busy' => false]);
        $this->assertDatabaseHas('users', ['id' => $provider->id, 'is_busy' => false]);

        // Verify ChatDismissed broadcast was dispatched to notify astrologer
        Event::assertDispatched(ChatDismissed::class, function ($event) use ($provider) {
            $receiverId = ($event->dismissedById == $event->session->consumer_id) 
                ? $event->session->provider_id 
                : $event->session->consumer_id;
            return (int) $receiverId === (int) $provider->id;
        });
    }

    /** @test */
    public function astrologer_cannot_accept_cancelled_chat()
    {
        Queue::fake();

        // 1. Create consumer with balance
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);

        // 3. Initiate Chat
        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id
        ]);
        $sessionId = $response->json('data.session.id');

        // 4. Cancel the chat request
        $this->actingAs($consumer)->postJson("/api/v1/chat/{$sessionId}/cancel");

        // 5. Astrologer tries to accept
        $acceptResponse = $this->actingAs($provider)->postJson("/api/v1/chat/{$sessionId}/accept");
        
        // Assert fail
        $acceptResponse->assertStatus(400);
        $acceptResponse->assertJsonFragment([
            'message' => 'Chat cannot be accepted. Session might have expired.'
        ]);
    }

    /** @test */
    public function user_going_offline_auto_cancels_initiated_chat()
    {
        Event::fake();
        Queue::fake();

        // 1. Create consumer with balance
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);

        // 3. Initiate Chat
        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id
        ]);
        $sessionId = $response->json('data.session.id');

        // 4. Consumer goes offline (fires presence offline API)
        $offlineResponse = $this->actingAs($consumer)->postJson('/api/v1/presence/offline');
        $offlineResponse->assertStatus(200);

        // Verify status transitioned to rejected and provider is free/available
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $sessionId,
            'status' => 'rejected'
        ]);
        $this->assertDatabaseHas('users', ['id' => $provider->id, 'is_busy' => false]);

        // Verify ChatDismissed broadcast was dispatched to notify provider/astrologer
        Event::assertDispatched(ChatDismissed::class, function ($event) use ($provider) {
            $receiverId = ($event->dismissedById == $event->session->consumer_id) 
                ? $event->session->provider_id 
                : $event->session->consumer_id;
            return (int) $receiverId === (int) $provider->id;
        });
    }

    /** @test */
    public function system_timeout_auto_dismisses_initiated_chat()
    {
        Event::fake();
        Queue::fake();

        // 1. Create consumer with balance
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);

        // 3. Initiate Chat
        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id
        ]);
        $sessionId = $response->json('data.session.id');

        // 4. Manually run the CleanupMissedSessionJob to simulate timeout
        $job = new \App\Jobs\CleanupMissedSessionJob($sessionId, 'chat');
        $job->handle();

        // 5. Verify status transitioned to rejected and players are free/available
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $sessionId,
            'status' => 'rejected'
        ]);
        $this->assertDatabaseHas('users', ['id' => $consumer->id, 'is_busy' => false]);
        $this->assertDatabaseHas('users', ['id' => $provider->id, 'is_busy' => false]);

        // Verify ChatDismissed broadcast was dispatched to notify astrologer & consumer
        Event::assertDispatched(ChatDismissed::class, function ($event) {
            return empty($event->dismissedById);
        });
    }
}
