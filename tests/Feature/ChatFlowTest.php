<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiate_chat_insufficient_balance()
    {
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create();
        Wallet::create(['user_id' => $consumer->id, 'balance' => 10]);

        $provider = User::factory()->create();
        Astrologer::create(['user_id' => $provider->id, 'chat_rate_per_minute' => 15]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/chat/initiate', [
            'provider_id' => $provider->id
        ]);

        $response->assertStatus(400);
    }
}
