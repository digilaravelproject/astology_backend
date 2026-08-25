<?php

namespace Tests\Feature;

use App\Events\CallAccepted;
use App\Events\CallDismissed;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatAccepted;
use App\Events\ChatDismissed;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\MessageSent;
use App\Jobs\SendPushNotificationJob;
use App\Listeners\SendCallPushNotificationListener;
use App\Listeners\SendChatInitiatedPushListener;
use App\Listeners\SendMessagePushNotificationListener;
use App\Listeners\SendSessionAcceptedPushListener;
use App\Listeners\SendSessionDismissedPushListener;
use App\Listeners\SendSessionEndedPushListener;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Wallet;
use App\Services\Notification\PushNotificationPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CallChatNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $consumer;
    protected User $provider;
    protected Astrologer $astrologerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Consumer User with wallet
        $this->consumer = User::factory()->create([
            'name' => 'Alice Consumer',
            'user_type' => 'user',
        ]);
        Wallet::create([
            'user_id' => $this->consumer->id,
            'balance' => 500.00,
        ]);
        UserDevice::create([
            'user_id' => $this->consumer->id,
            'fcm_token' => 'fcm_token_consumer_123',
            'device_type' => 'android',
            'is_active' => true,
        ]);

        // 2. Create Astrologer Provider with device and pricing
        $this->provider = User::factory()->create([
            'name' => 'Dr. Bob Astrologer',
            'user_type' => 'astrologer',
        ]);
        $this->astrologerProfile = Astrologer::create([
            'user_id' => $this->provider->id,
            'chat_rate_per_minute' => 10.00,
            'call_rate_per_minute' => 15.00,
            'is_chat_online' => true,
            'is_call_online' => true,
            'is_active' => true,
        ]);
        Wallet::create([
            'user_id' => $this->provider->id,
            'balance' => 100.00,
        ]);
        UserDevice::create([
            'user_id' => $this->provider->id,
            'fcm_token' => 'fcm_token_provider_456',
            'device_type' => 'android',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function chat_initiation_triggers_push_notification_to_astrologer()
    {
        Queue::fake();

        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 10.00,
            'status' => 'initiated',
            'question' => 'How will my career progress?',
        ]);

        $event = new ChatInitiated($session, $this->consumer);
        $listener = new SendChatInitiatedPushListener();
        $listener->handle($event);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $tokensProperty = $reflection->getProperty('tokens');
            $tokensProperty->setAccessible(true);
            $tokens = $tokensProperty->getValue($job);

            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var PushNotificationPayload $payload */
            $payload = $payloadProperty->getValue($job);

            return in_array('fcm_token_provider_456', $tokens)
                && $payload->type === 'chat'
                && str_contains($payload->title, 'New Chat Request')
                && $payload->isDataOnly === false;
        });
    }

    /** @test */
    public function chat_acceptance_triggers_push_notification_to_consumer()
    {
        Queue::fake();

        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 10.00,
            'status' => 'ongoing',
            'accepted_at' => now(),
        ]);

        $event = new ChatAccepted($session, $this->provider);
        $listener = new SendSessionAcceptedPushListener();
        $listener->handle($event);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $tokensProperty = $reflection->getProperty('tokens');
            $tokensProperty->setAccessible(true);
            $tokens = $tokensProperty->getValue($job);

            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var PushNotificationPayload $payload */
            $payload = $payloadProperty->getValue($job);

            return in_array('fcm_token_consumer_123', $tokens)
                && $payload->type === 'chat'
                && str_contains($payload->title, 'Accepted')
                && $payload->isDataOnly === false;
        });
    }

    /** @test */
    public function chat_rejection_triggers_push_notification_to_consumer()
    {
        Queue::fake();

        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 10.00,
            'status' => 'rejected',
        ]);

        $event = new ChatDismissed($session, $this->provider->id, 'rejected');
        $listener = new SendSessionDismissedPushListener();
        $listener->handle($event);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $tokensProperty = $reflection->getProperty('tokens');
            $tokensProperty->setAccessible(true);
            $tokens = $tokensProperty->getValue($job);

            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var PushNotificationPayload $payload */
            $payload = $payloadProperty->getValue($job);

            return in_array('fcm_token_consumer_123', $tokens)
                && $payload->type === 'chat'
                && str_contains($payload->title, 'Declined');
        });
    }

    /** @test */
    public function chat_ended_triggers_summary_push_notifications_to_both_parties()
    {
        Queue::fake();

        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 10.00,
            'duration_seconds' => 180,
            'total_cost' => 30.00,
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        $event = new ChatEnded($session, $this->consumer->id);
        $listener = new SendSessionEndedPushListener();
        $listener->handle($event);

        // Dispatches 2 jobs: one for consumer, one for astrologer
        Queue::assertPushed(SendPushNotificationJob::class, 2);
    }

    /** @test */
    public function call_initiation_triggers_proper_push_notification_with_system_alert_for_closed_app()
    {
        Queue::fake();

        $session = CallSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 15.00,
            'status' => 'initiated',
            'call_type' => 'audio',
        ]);

        $event = new CallInitiated($session, [
            'id' => $this->consumer->id,
            'name' => $this->consumer->name,
            'profile_photo' => null,
            'offer' => 'dummy_sdp_offer',
        ]);
        $listener = new SendCallPushNotificationListener();
        $listener->handle($event);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $tokensProperty = $reflection->getProperty('tokens');
            $tokensProperty->setAccessible(true);
            $tokens = $tokensProperty->getValue($job);

            $payloadProperty = $reflection->getProperty('payload');
            $payloadProperty->setAccessible(true);
            /** @var PushNotificationPayload $payload */
            $payload = $payloadProperty->getValue($job);

            return in_array('fcm_token_provider_456', $tokens)
                && $payload->type === 'call'
                && str_contains($payload->title, 'Incoming audio call')
                && $payload->priority === 'high'
                && $payload->sound === 'call_ringtone'
                && $payload->isDataOnly === false // Must NOT be data-only so closed-app OS tray displays it!
                && ($payload->customData['screen_route'] ?? '') === '/call-room';
        });
    }

    /** @test */
    public function sending_chat_message_does_not_dispatch_fcm_push_notification()
    {
        Queue::fake();

        $session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'rate_per_minute' => 10.00,
            'status' => 'ongoing',
        ]);

        $message = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $this->consumer->id,
            'receiver_id' => $this->provider->id,
            'message' => 'Hello astrologer!',
            'type' => 'text',
        ]);

        // Trigger listener directly
        $event = new MessageSent($message, $this->provider->id);
        $listener = new SendMessagePushNotificationListener();
        $listener->handle($event);

        // Assert NO push notification job is dispatched
        Queue::assertNotPushed(SendPushNotificationJob::class);
    }

    /** @test */
    public function user_logout_deactivates_device_token_and_clears_fcm_token()
    {
        $this->consumer->fcm_token = 'fcm_token_consumer_123';
        $this->consumer->save();

        $this->assertTrue(UserDevice::where('user_id', $this->consumer->id)->where('is_active', true)->exists());

        $response = $this->actingAs($this->consumer)->postJson('/api/v1/user/logout', [
            'fcm_token' => 'fcm_token_consumer_123',
        ]);

        $response->assertStatus(200);
        $this->assertFalse(UserDevice::where('user_id', $this->consumer->id)->where('is_active', true)->exists());
        $this->assertNull($this->consumer->fresh()->fcm_token);
    }

    /** @test */
    public function astrologer_logout_deactivates_device_token_and_clears_fcm_token()
    {
        $this->provider->fcm_token = 'fcm_token_provider_456';
        $this->provider->save();

        $this->assertTrue(UserDevice::where('user_id', $this->provider->id)->where('is_active', true)->exists());

        $response = $this->actingAs($this->provider)->postJson('/api/v1/astrologer/logout', [
            'fcm_token' => 'fcm_token_provider_456',
        ]);

        $response->assertStatus(200);
        $this->assertFalse(UserDevice::where('user_id', $this->provider->id)->where('is_active', true)->exists());
        $this->assertNull($this->provider->fresh()->fcm_token);
    }

    /** @test */
    public function registering_new_device_token_deactivates_previous_device_tokens_for_same_user()
    {
        // Provider already has fcm_token_provider_456 active from setUp
        $this->assertTrue(UserDevice::where('user_id', $this->provider->id)->where('fcm_token', 'fcm_token_provider_456')->value('is_active'));

        // Provider logs in on a new device with new token
        $response = $this->actingAs($this->provider)->postJson('/api/v1/astrologer/device-token', [
            'fcm_token' => 'fcm_token_new_device_789',
            'device_type' => 'android',
        ]);

        $response->assertStatus(200);

        // Old device token must now be deactivated
        $this->assertFalse(UserDevice::where('user_id', $this->provider->id)->where('fcm_token', 'fcm_token_provider_456')->value('is_active'));

        // New device token must be active
        $this->assertTrue(UserDevice::where('user_id', $this->provider->id)->where('fcm_token', 'fcm_token_new_device_789')->value('is_active'));
    }
}
