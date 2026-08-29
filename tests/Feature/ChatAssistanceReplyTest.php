<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Astrologer;
use App\Models\ChatAssistanceMessage;
use App\Models\ChatAssistanceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatAssistanceReplyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astroUser;
    protected Astrologer $astrologer;
    protected ChatAssistanceSession $assistanceSession;

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

        $this->assistanceSession = ChatAssistanceSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astroUser->id,
        ]);
    }

    public function test_send_chat_assistance_message_without_reply(): void
    {
        Event::fake([MessageSent::class]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/chat-assistance/{$this->assistanceSession->id}/message", [
            'message' => 'Hello Guru ji, need assistance.',
            'type' => 'text',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.reply_to_id', null);

        $this->assertDatabaseHas('chat_assistance_messages', [
            'chat_assistance_session_id' => $this->assistanceSession->id,
            'message' => 'Hello Guru ji, need assistance.',
            'reply_to_id' => null,
        ]);
    }

    public function test_send_chat_assistance_message_with_reply(): void
    {
        Event::fake([MessageSent::class]);

        $initialMsg = ChatAssistanceMessage::create([
            'chat_assistance_session_id' => $this->assistanceSession->id,
            'sender_id' => $this->astroUser->id,
            'receiver_id' => $this->user->id,
            'message' => 'Please share your query.',
            'type' => 'text',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/chat-assistance/{$this->assistanceSession->id}/message", [
            'message' => 'Replying to your question with details.',
            'type' => 'text',
            'reply_to_id' => $initialMsg->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.reply_to_id', $initialMsg->id)
            ->assertJsonPath('data.message.reply_to.id', $initialMsg->id)
            ->assertJsonPath('data.message.reply_to.message', 'Please share your query.');

        $this->assertDatabaseHas('chat_assistance_messages', [
            'chat_assistance_session_id' => $this->assistanceSession->id,
            'message' => 'Replying to your question with details.',
            'reply_to_id' => $initialMsg->id,
        ]);

        Event::assertDispatched(MessageSent::class, function ($event) use ($initialMsg) {
            $msg = $event->messageData;
            return $msg->reply_to_id === $initialMsg->id && $msg->replyTo->id === $initialMsg->id;
        });
    }

    public function test_chat_assistance_get_messages_includes_reply_to(): void
    {
        $msg1 = ChatAssistanceMessage::create([
            'chat_assistance_session_id' => $this->assistanceSession->id,
            'sender_id' => $this->user->id,
            'receiver_id' => $this->astroUser->id,
            'message' => 'Original query',
            'type' => 'text',
        ]);

        $msg2 = ChatAssistanceMessage::create([
            'chat_assistance_session_id' => $this->assistanceSession->id,
            'sender_id' => $this->astroUser->id,
            'receiver_id' => $this->user->id,
            'message' => 'Reply to query',
            'type' => 'text',
            'reply_to_id' => $msg1->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/chat-assistance/{$this->assistanceSession->id}/messages");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $data = $response->json('data.data');
        $replyMsg = collect($data)->firstWhere('id', $msg2->id);

        $this->assertNotNull($replyMsg);
        $this->assertEquals($msg1->id, $replyMsg['reply_to_id']);
        $this->assertNotNull($replyMsg['reply_to']);
        $this->assertEquals('Original query', $replyMsg['reply_to']['message']);
    }

    public function test_sending_non_existent_reply_to_id_fails_validation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/chat-assistance/{$this->assistanceSession->id}/message", [
            'message' => 'Invalid reply id test',
            'type' => 'text',
            'reply_to_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reply_to_id']);
    }
}
