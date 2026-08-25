<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\Message;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatMessageReplyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astroUser;
    protected Astrologer $astrologer;
    protected ChatSession $chatSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Consumer User',
            'phone' => '9998887771',
            'country_code' => '+91',
            'password' => bcrypt('password'),
            'user_type' => 'user',
        ]);

        $this->astroUser = User::create([
            'name' => 'Astrologer Guru',
            'phone' => '9998887772',
            'country_code' => '+91',
            'password' => bcrypt('password'),
            'user_type' => 'astrologer',
        ]);

        $this->astrologer = Astrologer::create([
            'user_id' => $this->astroUser->id,
            'status' => 'approved',
            'is_online' => true,
        ]);

        $this->chatSession = ChatSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astroUser->id,
            'status' => 'accepted',
            'rate_per_minute' => 25.0,
            'started_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    public function test_normal_message_can_be_sent_without_reply_id(): void
    {
        Event::fake([MessageSent::class]);

        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson("/api/v1/chat/{$this->chatSession->id}/message", [
            'message' => 'Hello Guru ji, what does my chart say?',
            'type' => 'text',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.message', 'Hello Guru ji, what does my chart say?')
            ->assertJsonPath('data.message.reply_to_id', null);

        $this->assertDatabaseHas('messages', [
            'chat_session_id' => $this->chatSession->id,
            'sender_id' => $this->user->id,
            'message' => 'Hello Guru ji, what does my chart say?',
            'reply_to_id' => null,
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_message_can_reply_to_another_message_in_session(): void
    {
        Event::fake([MessageSent::class]);

        // Step 1: Astrologer sends an initial question message
        $initialMsg = Message::create([
            'chat_session_id' => $this->chatSession->id,
            'sender_id' => $this->astroUser->id,
            'receiver_id' => $this->user->id,
            'message' => 'What is your place of birth?',
            'type' => 'text',
        ]);

        // Step 2: Consumer user sends a reply referencing $initialMsg->id
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson("/api/v1/chat/{$this->chatSession->id}/message", [
            'message' => 'I was born in Varanasi.',
            'type' => 'text',
            'reply_to_id' => $initialMsg->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.message', 'I was born in Varanasi.')
            ->assertJsonPath('data.message.reply_to_id', $initialMsg->id)
            ->assertJsonPath('data.message.reply_to.id', $initialMsg->id)
            ->assertJsonPath('data.message.reply_to.message', 'What is your place of birth?');

        $this->assertDatabaseHas('messages', [
            'chat_session_id' => $this->chatSession->id,
            'sender_id' => $this->user->id,
            'message' => 'I was born in Varanasi.',
            'reply_to_id' => $initialMsg->id,
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($initialMsg) {
            $msg = $event->messageData;
            return $msg->reply_to_id === $initialMsg->id && $msg->replyTo->id === $initialMsg->id;
        });
    }

    public function test_reply_message_in_package_chat_session(): void
    {
        // Setup package purchase and package subsession
        $package = Package::create([
            'name' => 'Premium 30 Min Package',
            'default_amount' => 500,
            'default_duration' => 1800,
            'is_default' => false,
        ]);

        $purchase = PackagePurchase::create([
            'user_id' => $this->user->id,
            'astrologer_id' => $this->astroUser->id,
            'total_duration' => 1800,
            'remaining_duration' => 1800,
            'purchase_price' => 500,
            'commission_percentage' => 10,
            'admin_earnings' => 50,
            'astrologer_earnings' => 450,
            'status' => 'active',
        ]);

        $packageChatSession = ChatSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astroUser->id,
            'status' => 'ongoing',
            'rate_per_minute' => 0.0,
            'started_at' => now(),
            'accepted_at' => now(),
        ]);

        PackageSubSession::create([
            'package_purchase_id' => $purchase->id,
            'chat_session_id' => $packageChatSession->id,
            'mode' => 'chat',
            'started_at' => now(),
        ]);

        $firstMsg = Message::create([
            'chat_session_id' => $packageChatSession->id,
            'sender_id' => $this->user->id,
            'receiver_id' => $this->astroUser->id,
            'message' => 'Guru ji, starting our package consultation.',
            'type' => 'text',
        ]);

        // Astrologer replies to the user's message inside package session
        Sanctum::actingAs($this->astroUser, ['*']);

        $response = $this->postJson("/api/v1/chat/{$packageChatSession->id}/message", [
            'message' => 'Welcome! I am analyzing your chart now.',
            'type' => 'text',
            'reply_to_id' => $firstMsg->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.reply_to_id', $firstMsg->id)
            ->assertJsonPath('data.message.reply_to.message', 'Guru ji, starting our package consultation.');

        $this->assertDatabaseHas('messages', [
            'chat_session_id' => $packageChatSession->id,
            'sender_id' => $this->astroUser->id,
            'reply_to_id' => $firstMsg->id,
        ]);
    }

    public function test_get_messages_history_returns_reply_details(): void
    {
        $msg1 = Message::create([
            'chat_session_id' => $this->chatSession->id,
            'sender_id' => $this->user->id,
            'receiver_id' => $this->astroUser->id,
            'message' => 'Question 1',
            'type' => 'text',
        ]);

        $msg2 = Message::create([
            'chat_session_id' => $this->chatSession->id,
            'sender_id' => $this->astroUser->id,
            'receiver_id' => $this->user->id,
            'message' => 'Answer to Question 1',
            'type' => 'text',
            'reply_to_id' => $msg1->id,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson("/api/v1/chat/{$this->chatSession->id}/messages");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $messages = $response->json('data.data');
        $replyMsg = collect($messages)->firstWhere('id', $msg2->id);

        $this->assertNotNull($replyMsg);
        $this->assertEquals($msg1->id, $replyMsg['reply_to_id']);
        $this->assertNotNull($replyMsg['reply_to']);
        $this->assertEquals('Question 1', $replyMsg['reply_to']['message']);
    }

    public function test_sending_non_existent_reply_to_id_fails_validation(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson("/api/v1/chat/{$this->chatSession->id}/message", [
            'message' => 'Replying to ghost message',
            'type' => 'text',
            'reply_to_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reply_to_id']);
    }
}
