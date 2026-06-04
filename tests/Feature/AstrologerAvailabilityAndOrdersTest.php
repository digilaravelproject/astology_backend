<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\CallSession;
use App\Events\ChatQueueUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Broadcast;

class AstrologerAvailabilityAndOrdersTest extends TestCase
{
    use RefreshDatabase;

    private $consumer;
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();

        // Create consumer
        $this->consumer = User::factory()->create([
            'name' => 'Test Consumer',
            'is_online' => true,
            'user_type' => 'user'
        ]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        // Create provider
        $this->provider = User::factory()->create([
            'name' => 'Astrologer Aacharya',
            'is_online' => true,
            'user_type' => 'astrologer'
        ]);
        Astrologer::create([
            'user_id' => $this->provider->id,
            'is_online' => true,
            'chat_rate_per_minute' => 10.00,
            'call_rate_per_minute' => 12.00
        ]);
        Wallet::create(['user_id' => $this->provider->id, 'balance' => 100.00]);
    }

    /** @test */
    public function it_calculates_busy_status_dynamically_based_on_active_sessions()
    {
        // 1. Initially, astrologer is free
        $response = $this->actingAs($this->consumer)->getJson("/api/v1/user/astrologers/{$this->provider->astrologer->id}");
        $response->assertStatus(200);
        $this->assertFalse($response->json('data.astrologer.is_busy'));

        // 2. Create an ongoing chat session
        $chatSession = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 10.00,
            'started_at' => now(),
        ]);

        // Now, profile should report busy
        $response = $this->actingAs($this->consumer)->getJson("/api/v1/user/astrologers/{$this->provider->astrologer->id}");
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.astrologer.is_busy'));

        // List should also report busy
        $listResponse = $this->actingAs($this->consumer)->getJson('/api/v1/user/astrologers');
        $listResponse->assertStatus(200);
        $astrologers = $listResponse->json('data.astrologers');
        $this->assertCount(1, $astrologers);
        $this->assertTrue($astrologers[0]['is_busy']);

        // Complete the chat session
        $chatSession->status = 'completed';
        $chatSession->save();

        // Astrologer should be free again
        $response = $this->actingAs($this->consumer)->getJson("/api/v1/user/astrologers/{$this->provider->astrologer->id}");
        $response->assertStatus(200);
        $this->assertFalse($response->json('data.astrologer.is_busy'));
    }

    /** @test */
    public function it_creates_session_with_waiting_status_if_astrologer_is_busy()
    {
        // Place astrologer in active chat session
        ChatSession::create([
            'consumer_id' => User::factory()->create()->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 10.00,
            'started_at' => now(),
        ]);

        // Another user initiates chat
        $secondConsumer = User::factory()->create();
        Wallet::create(['user_id' => $secondConsumer->id, 'balance' => 300.00]);

        $response = $this->actingAs($secondConsumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $this->provider->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals('waiting', $response->json('data.session.status'));

        // Confirm wait list shows up in orders API
        $ordersResponse = $this->actingAs($this->provider)->getJson('/api/v1/astrologer/orders?status=waiting');
        $ordersResponse->assertStatus(200);
        $this->assertCount(1, $ordersResponse->json('data.orders'));
        $this->assertEquals('waiting', $ordersResponse->json('data.orders.0.status'));
        $this->assertEquals(1, $ordersResponse->json('data.orders.0.queue_position'));
    }

    /** @test */
    public function it_orders_waiting_chat_queue_fifo_and_blocks_out_of_order_acceptance()
    {
        ChatSession::create([
            'consumer_id' => User::factory()->create()->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 10.00,
            'started_at' => now(),
        ]);

        $userB = User::factory()->create(['name' => 'User B']);
        $userC = User::factory()->create(['name' => 'User C']);
        Wallet::create(['user_id' => $userB->id, 'balance' => 300.00]);
        Wallet::create(['user_id' => $userC->id, 'balance' => 300.00]);

        $sessionB = ChatSession::create([
            'consumer_id' => $userB->id,
            'provider_id' => $this->provider->id,
            'status' => 'waiting',
            'rate_per_minute' => 10.00,
            'created_at' => now()->subMinute(),
        ]);

        $sessionC = ChatSession::create([
            'consumer_id' => $userC->id,
            'provider_id' => $this->provider->id,
            'status' => 'waiting',
            'rate_per_minute' => 10.00,
            'created_at' => now(),
        ]);

        $ordersResponse = $this->actingAs($this->provider)->getJson('/api/v1/astrologer/orders?status=waiting&type=chat');

        $ordersResponse->assertStatus(200);
        $this->assertEquals($sessionB->id, $ordersResponse->json('data.orders.0.session_id'));
        $this->assertEquals(1, $ordersResponse->json('data.orders.0.queue_position'));
        $this->assertEquals($sessionC->id, $ordersResponse->json('data.orders.1.session_id'));
        $this->assertEquals(2, $ordersResponse->json('data.orders.1.queue_position'));

        ChatSession::where('provider_id', $this->provider->id)
            ->where('status', 'ongoing')
            ->update(['status' => 'completed']);

        $acceptResponse = $this->actingAs($this->provider)->postJson("/api/v1/chat/{$sessionC->id}/accept");
        $acceptResponse->assertStatus(400);
        $acceptResponse->assertJsonFragment([
            'message' => 'Please accept the oldest waiting chat request first.',
        ]);
    }

    /** @test */
    public function it_prevents_astrologer_from_accepting_multiple_ongoing_sessions()
    {
        // 1. Create a waiting chat request
        $chatSession = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'waiting',
            'rate_per_minute' => 10.00,
        ]);

        // 2. Put astrologer in an active call session
        CallSession::create([
            'consumer_id' => User::factory()->create()->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 12.00,
            'started_at' => now(),
        ]);

        // 3. Attempting to accept the waiting chat session should fail
        $response = $this->actingAs($this->provider)->postJson("/api/v1/chat/{$chatSession->id}/accept");
        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonFragment(['message' => 'You are already in an active session.']);
    }

    /** @test */
    public function it_correctly_rejects_chat_and_call_sessions()
    {
        // Create initiated chat
        $chatSession = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'initiated',
        ]);

        // Reject chat
        $response = $this->actingAs($this->provider)->postJson("/api/v1/chat/{$chatSession->id}/reject");
        $response->assertStatus(200);

        // Assert it is rejected in DB
        $chatSession->refresh();
        $this->assertEquals('rejected', $chatSession->status);
        Event::assertDispatched(ChatQueueUpdated::class);

        // Create initiated call
        $callSession = CallSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'initiated',
        ]);

        // Reject call
        $response = $this->actingAs($this->provider)->postJson("/api/v1/call/{$callSession->id}/reject");
        $response->assertStatus(200);

        // Assert it is rejected in DB
        $callSession->refresh();
        $this->assertEquals('rejected', $callSession->status);
    }

    /** @test */
    public function it_retrieves_paginated_and_formatted_astrologer_orders()
    {
        // Create call history
        CallSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'completed',
            'rate_per_minute' => 12.00,
            'total_cost' => 60.00,
            'duration_seconds' => 300,
            'created_at' => now()->subMinutes(10),
        ]);

        // Create chat history
        ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'completed',
            'rate_per_minute' => 10.00,
            'total_cost' => 50.00,
            'duration_seconds' => 300,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->provider)->getJson('/api/v1/astrologer/orders');
        $response->assertStatus(200);

        $orders = $response->json('data.orders');
        $this->assertCount(2, $orders);

        // Order history is sorted newest first
        $this->assertEquals('chat', $orders[0]['request_type']);
        $this->assertEquals(50.00, $orders[0]['amount']);
        $this->assertEquals('call', $orders[1]['request_type']);
        $this->assertEquals(60.00, $orders[1]['amount']);
        $this->assertEquals('Test Consumer', $orders[0]['user_name']);

        // Test filtering by type = call
        $callFilter = $this->actingAs($this->provider)->getJson('/api/v1/astrologer/orders?type=call');
        $callFilter->assertStatus(200);
        $this->assertCount(1, $callFilter->json('data.orders'));
        $this->assertEquals('call', $callFilter->json('data.orders.0.request_type'));
    }
}
