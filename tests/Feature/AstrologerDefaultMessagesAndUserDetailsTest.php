<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\AstrologerDefaultMessage;
use App\Models\ChatSession;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AstrologerDefaultMessagesAndUserDetailsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_update_profile_with_extra_fields()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);

        $response = $this->actingAs($user)->putJson('/api/v1/user/profileInAppUpdate', [
            'name' => 'Amit Sharma',
            'gender' => 'male',
            'date_of_birth' => '1995-10-12',
            'time_of_birth' => '08:45',
            'place_of_birth' => 'Mumbai, India',
            'relationship_status' => 'Single',
            'occupation' => 'Engineer',
            'languages' => ['English', 'Hindi']
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.time_of_birth', '08:45');
        $response->assertJsonPath('data.user.relationship_status', 'Single');
        $response->assertJsonPath('data.user.occupation', 'Engineer');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'relationship_status' => 'Single',
            'occupation' => 'Engineer',
            'time_of_birth' => '08:45'
        ]);
    }

    /** @test */
    public function user_profile_update_validation_constraints()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);

        // Invalid time format, missing languages
        $response = $this->actingAs($user)->putJson('/api/v1/user/profileInAppUpdate', [
            'name' => 'Amit Sharma',
            'gender' => 'male',
            'date_of_birth' => '1995-10-12',
            'time_of_birth' => '25:00', // Invalid hour
            'place_of_birth' => 'Mumbai, India',
            'relationship_status' => 'Single',
            'occupation' => 'Engineer'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chat_initiation_ingests_optional_question()
    {
        Queue::fake();

        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 10.00
        ]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id,
            'question' => 'Will I get a promotion soon?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.session.question', 'Will I get a promotion soon?');

        $this->assertDatabaseHas('chat_sessions', [
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'question' => 'Will I get a promotion soon?',
            'status' => 'initiated'
        ]);
    }

    /** @test */
    public function accepting_chat_creates_system_message_with_user_details()
    {
        Event::fake([MessageSent::class]);
        Queue::fake();

        // 1. Create consumer with complete details
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create([
            'name' => 'Test Consumer',
            'gender' => 'female',
            'date_of_birth' => '1992-04-20',
            'time_of_birth' => '18:30:00',
            'place_of_birth' => 'Delhi, India',
            'relationship_status' => 'Married',
            'occupation' => 'Doctor',
            'is_online' => true,
            'is_busy' => false
        ]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 10.00
        ]);

        // 3. Initiate Chat
        $initResponse = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id,
            'question' => 'How will my marriage life go?',
        ]);
        $sessionId = $initResponse->json('data.session.id');

        // 4. Accept Chat
        $acceptResponse = $this->actingAs($provider)->postJson("/api/v1/chat/{$sessionId}/accept");
        $acceptResponse->assertStatus(200);

        // 5. Verify system message exists with all info
        $this->assertDatabaseHas('messages', [
            'chat_session_id' => $sessionId,
            'type' => 'system'
        ]);

        $message = Message::where('chat_session_id', $sessionId)->where('type', 'system')->first();
        $this->assertStringContainsString('Test Consumer', $message->message);
        $this->assertStringContainsString('1992-04-20', $message->message);
        $this->assertStringContainsString('18:30', $message->message);
        $this->assertStringContainsString('Delhi, India', $message->message);
        $this->assertStringContainsString('Married', $message->message);
        $this->assertStringContainsString('Doctor', $message->message);
        $this->assertStringContainsString('How will my marriage life go?', $message->message);

        // Verify MessageSent event was broadcasted to provider (astrologer)
        Event::assertDispatched(MessageSent::class, function ($event) use ($provider, $message) {
            return (int) $event->receiverId === (int) $provider->id && (int) $event->messageData->id === (int) $message->id;
        });
    }

    /** @test */
    public function accepting_chat_sends_personalized_greeting_message()
    {
        Event::fake([MessageSent::class]);
        Queue::fake();

        // 1. Create consumer with complete details
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create([
            'name' => 'Rajesh',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'time_of_birth' => '12:00:00',
            'place_of_birth' => 'Pune',
            'is_online' => true,
            'is_busy' => false
        ]);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 500.00]);

        // 2. Create online provider
        /** @var \App\Models\User $provider */
        $provider = User::factory()->create(['name' => 'Guru Dev', 'is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 10.00
        ]);

        // 3. Create default template for astrologer
        AstrologerDefaultMessage::create([
            'astrologer_id' => $provider->id,
            'title' => 'Welcome Template',
            'content' => 'Hello {{user_name}}, I am {{astrologer_name}}. Welcome for session {{session_id}}.',
            'is_default' => true
        ]);

        // 4. Initiate Chat
        $initResponse = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id,
        ]);
        $sessionId = $initResponse->json('data.session.id');

        // 5. Accept Chat
        $acceptResponse = $this->actingAs($provider)->postJson("/api/v1/chat/{$sessionId}/accept");
        $acceptResponse->assertStatus(200);

        // 6. Verify personalized message exists in database
        $this->assertDatabaseHas('messages', [
            'chat_session_id' => $sessionId,
            'type' => 'text',
            'sender_id' => $provider->id,
            'receiver_id' => $consumer->id
        ]);

        $message = Message::where('chat_session_id', $sessionId)->where('type', 'text')->first();
        $expectedContent = "Hello Rajesh, I am Guru Dev. Welcome for session {$sessionId}.";
        $this->assertEquals($expectedContent, $message->message);

        // Verify MessageSent event was broadcasted to consumer (user)
        Event::assertDispatched(MessageSent::class, function ($event) use ($consumer, $message) {
            return (int) $event->receiverId === (int) $consumer->id && (int) $event->messageData->id === (int) $message->id;
        });
    }

    /** @test */
    public function astrologer_can_perform_crud_on_default_messages()
    {
        /** @var \App\Models\User $astrologer */
        $astrologer = User::factory()->create(['user_type' => 'astrologer']);

        // 1. Create message template
        $response = $this->actingAs($astrologer)->postJson('/api/v1/astrologer/default-messages', [
            'title' => 'Standard Greeting',
            'content' => 'Hello {{user_name}}',
            'is_default' => true
        ]);
        $response->assertStatus(201);
        $messageId = $response->json('data.id');

        $this->assertDatabaseHas('astrologer_default_messages', [
            'id' => $messageId,
            'astrologer_id' => $astrologer->id,
            'is_default' => true
        ]);

        // 2. Fetch list
        $listResponse = $this->actingAs($astrologer)->getJson('/api/v1/astrologer/default-messages');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1, 'data');

        // 3. Fetch active template
        $activeResponse = $this->actingAs($astrologer)->getJson('/api/v1/astrologer/default-messages/active');
        $activeResponse->assertStatus(200);
        $activeResponse->assertJsonPath('data.id', $messageId);

        // 4. Create second template as default (should deactivate first)
        $secondResponse = $this->actingAs($astrologer)->postJson('/api/v1/astrologer/default-messages', [
            'title' => 'Alternate Greeting',
            'content' => 'Hi {{user_name}}',
            'is_default' => true
        ]);
        $secondResponse->assertStatus(201);
        $secondId = $secondResponse->json('data.id');

        // Verify atomic default handling
        $this->assertDatabaseHas('astrologer_default_messages', ['id' => $messageId, 'is_default' => false]);
        $this->assertDatabaseHas('astrologer_default_messages', ['id' => $secondId, 'is_default' => true]);

        // 5. Set first template back to default via setDefault API
        $setDefResponse = $this->actingAs($astrologer)->postJson("/api/v1/astrologer/default-messages/{$messageId}/set-default");
        $setDefResponse->assertStatus(200);
        $this->assertDatabaseHas('astrologer_default_messages', ['id' => $messageId, 'is_default' => true]);
        $this->assertDatabaseHas('astrologer_default_messages', ['id' => $secondId, 'is_default' => false]);

        // 6. Update template content
        $updateResponse = $this->actingAs($astrologer)->putJson("/api/v1/astrologer/default-messages/{$messageId}", [
            'title' => 'Updated Title',
            'content' => 'Pranam {{user_name}}',
            'is_default' => true
        ]);
        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('astrologer_default_messages', [
            'id' => $messageId,
            'title' => 'Updated Title',
            'content' => 'Pranam {{user_name}}'
        ]);

        // 7. Delete template
        $deleteResponse = $this->actingAs($astrologer)->deleteJson("/api/v1/astrologer/default-messages/{$messageId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('astrologer_default_messages', ['id' => $messageId]);
    }
}
