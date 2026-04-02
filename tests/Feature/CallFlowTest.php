<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;

class CallFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiate_call_insufficient_balance()
    {
        /** @var \App\Models\User $consumer */
        $consumer = User::factory()->create();
        if(!class_exists('App\Models\Wallet')) {
            $this->markTestSkipped('Wallet model not found.');
        }
        Wallet::create(['user_id' => $consumer->id, 'balance' => 10]); // Less than 15*5=75

        $provider = User::factory()->create();
        Astrologer::create(['user_id' => $provider->id, 'call_rate_per_minute' => 15]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/call/initiate', [
            'provider_id' => $provider->id,
            'offer' => 'sdp-offer'
        ]);

        $response->assertStatus(400); // Because we return 400 with ApiResponse::error
    }
}
