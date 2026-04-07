<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\ChatSession;
use App\Events\MessageSent;
use App\Events\ChatInitiated;
use App\Events\ChatAccepted;
use App\Events\ChatEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function message_sent_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $session = ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $receiver->id,
            'status' => 'ongoing'
        ]);

        $message = Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'message' => 'Hello test',
            'type' => 'text'
        ]);

        event(new MessageSent($message, $receiver->id));

        Event::assertDispatched(MessageSent::class, function ($event) use ($receiver) {
            return $event->receiverId === $receiver->id;
        });
    }

    /** @test */
    public function chat_accepted_event_is_dispatched()
    {
        Event::fake();

        $user = User::factory()->create();
        $provider = User::factory()->create();
        $session = ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $provider->id,
            'status' => 'initiated'
        ]);

        event(new ChatAccepted($session));

        Event::assertDispatched(ChatAccepted::class, function ($event) use ($user) {
            return $event->session->consumer_id === $user->id;
        });
    }
}
