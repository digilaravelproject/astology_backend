<?php

namespace Tests\Feature;

use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatAssistanceSession;
use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\ChatAssistanceService;
use App\Services\Notification\PushNotificationPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatAssistanceNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('chat_assistance_enabled', '1');
    }

    public function test_non_existing_user_cannot_initiate_assistant_chat()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);

        $service = app(ChatAssistanceService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You can only connect with this astrologer's assistant after having at least one consultation with the astrologer.");

        $service->initiateChat($consumer->id, $provider->id);
    }

    public function test_existing_user_with_past_chat_session_can_initiate_assistant_chat()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);

        // Create past chat session
        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        $service = app(ChatAssistanceService::class);
        $session = $service->initiateChat($consumer->id, $provider->id);

        $this->assertInstanceOf(ChatAssistanceSession::class, $session);
        $this->assertEquals($consumer->id, $session->consumer_id);
        $this->assertEquals($provider->id, $session->provider_id);
    }

    public function test_existing_user_with_past_call_session_can_initiate_assistant_chat()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);

        // Create past call session
        CallSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        $service = app(ChatAssistanceService::class);
        $session = $service->initiateChat($consumer->id, $provider->id);

        $this->assertInstanceOf(ChatAssistanceSession::class, $session);
        $this->assertEquals($consumer->id, $session->consumer_id);
    }

    public function test_blocked_user_cannot_initiate_assistant_chat_even_with_history()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);

        // Past session
        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        // Block relationship
        UserBlock::create([
            'blocker_id' => $provider->id,
            'blocked_id' => $consumer->id,
        ]);

        $service = app(ChatAssistanceService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        $service->initiateChat($consumer->id, $provider->id);
    }

    public function test_user_message_creates_and_dispatches_notification_with_correct_payload()
    {
        $consumer = User::factory()->create(['name' => 'Rahul Sharma', 'user_type' => 'user']);
        $provider = User::factory()->create(['name' => 'Acharya Ji', 'user_type' => 'astrologer']);

        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        $service = app(ChatAssistanceService::class);
        $session = $service->initiateChat($consumer->id, $provider->id);

        $message = $service->sendMessage($session->id, $consumer->id, [
            'message' => 'Hello Acharya Ji, need guidance on gemstone.',
            'type'    => 'text',
        ]);

        $this->assertEquals('Hello Acharya Ji, need guidance on gemstone.', $message->message);
        $this->assertEquals($consumer->id, $message->sender_id);
        $this->assertEquals($provider->id, $message->receiver_id);

        // Verify throttle key exists for receiver
        $this->assertTrue(Cache::has("assistant_chat_throttle_{$provider->id}_{$session->id}"));
    }

    public function test_assistant_message_creates_notification_with_astrologer_assistant_branding()
    {
        $consumer = User::factory()->create(['name' => 'Rahul Sharma', 'user_type' => 'user']);
        $provider = User::factory()->create(['name' => 'Acharya Ji', 'user_type' => 'astrologer']);

        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        $service = app(ChatAssistanceService::class);
        $session = $service->initiateChat($consumer->id, $provider->id);

        // Assistant/Astrologer sends reply
        $message = $service->sendMessage($session->id, $provider->id, [
            'message' => 'Sure Rahul, let me consult Guruji and get back to you.',
            'type'    => 'text',
        ]);

        $this->assertEquals($provider->id, $message->sender_id);
        $this->assertEquals($consumer->id, $message->receiver_id);

        // Test payload builder branding
        $payload = PushNotificationPayload::forChatAssistance(
            sessionId: $session->id,
            senderId: $provider->id,
            senderName: $provider->name,
            messagePreview: $message->message,
            direction: 'assistant_to_user',
            astrologerName: $provider->name
        );

        $this->assertEquals('Acharya Ji (Assistant)', $payload->title);
        $this->assertEquals('/assistant-chat-room', $payload->customData['screen_route']);
        $this->assertEquals('chat_assistance', $payload->type);

        // Verify throttle key set for consumer
        $this->assertTrue(Cache::has("assistant_chat_throttle_{$consumer->id}_{$session->id}"));
    }

    public function test_assistant_message_does_not_alert_astrologer()
    {
        $consumer = User::factory()->create(['name' => 'Rahul', 'user_type' => 'user']);
        $provider = User::factory()->create(['name' => 'Acharya Ji', 'user_type' => 'astrologer']);

        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'completed',
        ]);

        $service = app(ChatAssistanceService::class);
        $session = $service->initiateChat($consumer->id, $provider->id);

        // Sending as astrologer/assistant
        $service->sendMessage($session->id, $provider->id, [
            'message' => 'Replying to consumer.',
            'type'    => 'text',
        ]);

        // Throttle key should ONLY be set for consumer receiver, NOT provider
        $this->assertFalse(Cache::has("assistant_chat_throttle_{$provider->id}_{$session->id}"));
        $this->assertTrue(Cache::has("assistant_chat_throttle_{$consumer->id}_{$session->id}"));
    }
}
