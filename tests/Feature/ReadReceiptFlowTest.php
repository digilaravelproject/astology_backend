<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\Message;
use App\Events\MessageStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class ReadReceiptFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUsers(): array
    {
        $consumer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_read@test.com',
            'password' => bcrypt('password'),
            'user_type' => 'user',
        ]);
        $provider = User::create([
            'name' => 'Test Astrologer',
            'email' => 'astrologer_read@test.com',
            'password' => bcrypt('password'),
            'user_type' => 'astrologer',
        ]);
        return [$consumer, $provider];
    }

    private function createActiveSession($consumer, $provider): ChatSession
    {
        return ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 15,
            'started_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    /**
     * @test
     * Test 1: markAsRead marks only unread messages and broadcasts event with reader_id and read_at
     */
    public function mark_as_read_marks_unread_messages_and_broadcasts_read_receipt()
    {
        Event::fake([MessageStatusUpdated::class]);

        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        // Provider sends 3 messages to consumer
        $msg1 = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'Hello!',
            'type' => 'text',
        ]);
        $msg2 = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'How are you?',
            'type' => 'text',
        ]);
        $msg3 = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'Please reply',
            'type' => 'text',
        ]);

        // Verify all 3 are unread
        $this->assertFalse($msg1->fresh()->is_read);
        $this->assertFalse($msg2->fresh()->is_read);
        $this->assertFalse($msg3->fresh()->is_read);

        // Consumer calls markAsRead
        $response = $this->actingAs($consumer)
            ->postJson("/api/v1/chat/{$session->id}/read");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Messages marked as read',
            ]);

        // Verify response contains count and IDs
        $this->assertEquals(3, $response->json('data.marked_count'));
        $this->assertCount(3, $response->json('data.message_ids'));

        // Verify all 3 are now read in DB
        $this->assertTrue($msg1->fresh()->is_read);
        $this->assertTrue($msg2->fresh()->is_read);
        $this->assertTrue($msg3->fresh()->is_read);

        // Verify is_delivered is also set to true
        $this->assertTrue($msg1->fresh()->is_delivered);
        $this->assertTrue($msg2->fresh()->is_delivered);
        $this->assertTrue($msg3->fresh()->is_delivered);

        // Verify the MessageStatusUpdated event was broadcasted
        Event::assertDispatched(MessageStatusUpdated::class, function ($event) use ($provider, $consumer, $session) {
            return $event->receiverId === $provider->id   // Event goes to the SENDER (provider)
                && $event->status === 'seen'
                && $event->readerId === $consumer->id     // reader_id = consumer
                && $event->sessionId === $session->id
                && $event->readAt !== null                 // read_at is present
                && count($event->messageIds) === 3;
            });
    }

    /**
     * @test
     * Test 2: markAsRead returns early if no unread messages exist
     */
    public function mark_as_read_returns_early_when_no_unread_messages()
    {
        Event::fake([MessageStatusUpdated::class]);

        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        // Create message that is ALREADY read
        Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'Already read msg',
            'type' => 'text',
            'is_read' => true,
        ]);

        // Call markAsRead
        $response = $this->actingAs($consumer)
            ->postJson("/api/v1/chat/{$session->id}/read");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'No unread messages to mark',
            ]);

        // No event should be broadcasted
        Event::assertNotDispatched(MessageStatusUpdated::class);
    }

    /**
     * @test
     * Test 3: Non-participant cannot call markAsRead
     */
    public function non_participant_cannot_mark_as_read()
    {
        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider_read@test.com',
            'password' => bcrypt('password'),
            'user_type' => 'user',
        ]);

        $response = $this->actingAs($outsider)
            ->postJson("/api/v1/chat/{$session->id}/read");

        $response->assertStatus(403);
    }

    /**
     * @test
     * Test 4: syncStatus with 'delivered' marks only is_delivered, not is_read
     */
    public function sync_status_delivered_marks_only_delivered()
    {
        Event::fake([MessageStatusUpdated::class]);

        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        $msg = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'New message',
            'type' => 'text',
        ]);

        // Consumer syncs status as 'delivered'
        $response = $this->actingAs($consumer)
            ->postJson("/api/v1/chat/{$session->id}/sync-status", [
                'status' => 'delivered',
                'message_ids' => [$msg->id],
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Status updated']);

        $freshMsg = $msg->fresh();
        $this->assertTrue($freshMsg->is_delivered);  // delivered = true
        $this->assertFalse($freshMsg->is_read);       // read still false

        // Event should contain reader_id and read_at
        Event::assertDispatched(MessageStatusUpdated::class, function ($event) use ($provider, $consumer, $session) {
            return $event->receiverId === $provider->id
                && $event->status === 'delivered'
                && $event->readerId === $consumer->id
                && $event->readAt !== null;
        });
    }

    /**
     * @test
     * Test 5: syncStatus with 'seen' marks both is_delivered and is_read
     */
    public function sync_status_seen_marks_both_delivered_and_read()
    {
        Event::fake([MessageStatusUpdated::class]);

        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        $msg = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'Another message',
            'type' => 'text',
        ]);

        // Consumer syncs status as 'seen'
        $response = $this->actingAs($consumer)
            ->postJson("/api/v1/chat/{$session->id}/sync-status", [
                'status' => 'seen',
                'message_ids' => [$msg->id],
            ]);

        $response->assertOk();

        $freshMsg = $msg->fresh();
        $this->assertTrue($freshMsg->is_delivered);
        $this->assertTrue($freshMsg->is_read);

        // Event should be broadcasted to the provider (sender)
        Event::assertDispatched(MessageStatusUpdated::class, function ($event) use ($provider) {
            return $event->receiverId === $provider->id
                && $event->status === 'seen';
        });
    }

    /**
     * @test
     * Test 6: Event broadcastWith includes all required fields
     */
    public function event_broadcast_payload_contains_all_required_fields()
    {
        $event = new MessageStatusUpdated(
            [1, 2, 3],           // messageIds
            'seen',              // status
            99,                  // receiverId (the sender of original messages)
            50,                  // sessionId
            42,                  // readerId (who read the messages)
            '2026-06-04T12:30:45+00:00'  // readAt
        );

        $payload = $event->broadcastWith();

        $this->assertEquals([1, 2, 3], $payload['message_ids']);
        $this->assertEquals('seen', $payload['status']);
        $this->assertEquals(50, $payload['session_id']);
        $this->assertEquals(42, $payload['reader_id']);
        $this->assertEquals('2026-06-04T12:30:45+00:00', $payload['read_at']);

        // Verify broadcastAs
        $this->assertEquals('MessageStatusUpdated', $event->broadcastAs());

        // Verify it broadcasts on correct channel
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals('private-user.99', $channels[0]->name);
    }

    /**
     * @test
     * Test 7: markAsRead does NOT mark sender's own messages
     */
    public function mark_as_read_does_not_mark_senders_own_messages()
    {
        Event::fake([MessageStatusUpdated::class]);

        [$consumer, $provider] = $this->createTestUsers();
        $session = $this->createActiveSession($consumer, $provider);

        // Consumer sends a message TO provider
        $sentMsg = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $consumer->id,
            'receiver_id' => $provider->id,
            'message' => 'I sent this',
            'type' => 'text',
        ]);

        // Provider sends a message TO consumer (this should get marked)
        $receivedMsg = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id,
            'message' => 'Reply from astrologer',
            'type' => 'text',
        ]);

        // Consumer calls markAsRead
        $response = $this->actingAs($consumer)
            ->postJson("/api/v1/chat/{$session->id}/read");

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.marked_count'));

        // Only received message should be marked
        $this->assertTrue($receivedMsg->fresh()->is_read);
        $this->assertFalse($sentMsg->fresh()->is_read);
    }
}
