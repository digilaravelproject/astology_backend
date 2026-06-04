<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserChatCancelApiTest extends TestCase
{
    use RefreshDatabase;

    private User $consumer;
    private User $otherUser;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = User::factory()->create([
            'user_type' => 'user',
        ]);

        $this->otherUser = User::factory()->create([
            'user_type' => 'user',
        ]);

        $this->provider = User::factory()->create([
            'user_type' => 'astrologer',
        ]);
    }

    public function test_user_can_cancel_own_initiated_chat(): void
    {
        Event::fake();

        $session = $this->createChatSession('initiated');

        $response = $this->actingAs($this->consumer)
            ->postJson("/api/v1/chat/{$session->id}/cancel");

        $response->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'message' => 'Chat cancelled successfully.',
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'status' => 'cancelled',
        ]);

        $this->assertNotNull($session->fresh()->ended_at);
    }

    public function test_user_can_cancel_own_waiting_chat(): void
    {
        Event::fake();

        $session = $this->createChatSession('waiting');

        $response = $this->actingAs($this->consumer)
            ->postJson("/api/v1/chat/{$session->id}/cancel");

        $response->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'message' => 'Chat cancelled successfully.',
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_other_user_cannot_cancel_someone_elses_chat(): void
    {
        Event::fake();

        $session = $this->createChatSession('initiated');

        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/chat/{$session->id}/cancel");

        $response->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'message' => 'You are not authorized to cancel this chat.',
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'status' => 'initiated',
        ]);
    }

    public function test_provider_cannot_cancel_chat_from_user_cancel_endpoint(): void
    {
        Event::fake();

        $session = $this->createChatSession('initiated');

        $response = $this->actingAs($this->provider)
            ->postJson("/api/v1/chat/{$session->id}/cancel");

        $response->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'message' => 'You are not authorized to cancel this chat.',
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'status' => 'initiated',
        ]);
    }

    /**
     * @dataProvider terminalChatStatuses
     */
    public function test_terminal_chat_statuses_return_clear_error_message(string $status): void
    {
        Event::fake();

        $session = $this->createChatSession($status);

        $response = $this->actingAs($this->consumer)
            ->postJson("/api/v1/chat/{$session->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => "This chat is already {$status}.",
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'status' => $status,
        ]);
    }

    public static function terminalChatStatuses(): array
    {
        return [
            'cancelled' => ['cancelled'],
            'rejected' => ['rejected'],
            'completed' => ['completed'],
        ];
    }

    private function createChatSession(string $status): ChatSession
    {
        return ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => $status,
            'rate_per_minute' => 15.00,
        ]);
    }
}
