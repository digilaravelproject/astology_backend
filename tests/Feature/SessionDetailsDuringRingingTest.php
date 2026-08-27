<?php

namespace Tests\Feature;

use App\Events\CallInitiated;
use App\Events\ChatInitiated;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SessionDetailsDuringRingingTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_initiated_and_current_session_include_full_consumer_birth_details(): void
    {
        Event::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $consumer = User::factory()->create([
            'user_type'      => 'user',
            'name'           => 'Firoz Khan',
            'phone'          => '9936211796',
            'gender'         => 'male',
            'date_of_birth'  => '2006-08-25',
            'time_of_birth'  => '15:50:00',
            'place_of_birth' => 'Lucknow, Uttar Pradesh',
            'latitude'       => 26.8138138,
            'longitude'      => 80.9020587,
            'city'           => 'Lucknow',
            'country'        => 'India',
        ]);

        Wallet::create([
            'user_id' => $consumer->id,
            'balance' => 1000.00,
        ]);

        $astrologerUser = User::factory()->create([
            'user_type' => 'astrologer',
            'name'      => 'Acharya Sharma',
        ]);

        Astrologer::factory()->create([
            'user_id'              => $astrologerUser->id,
            'is_call_enabled'      => true,
            'is_online'            => true,
            'call_rate_per_minute' => 10.00,
            'status'               => 'approved',
        ]);

        // 1. Consumer initiates call
        $response = $this->actingAs($consumer, 'sanctum')->postJson('/api/v1/call/initiate', [
            'provider_id' => $astrologerUser->id,
            'offer'       => 'test-sdp-offer',
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(CallInitiated::class, function ($event) {
            $payload = $event->broadcastWith();
            return isset($payload['session']['consumer']['name'])
                && $payload['session']['consumer']['name'] === 'Firoz Khan'
                && $payload['callerData']['place_of_birth'] === 'Lucknow, Uttar Pradesh'
                && $payload['callerData']['time_of_birth'] === '15:50:00';
        });

        // 2. Astrologer checks /api/v1/call/current-session during ringing/initiated
        $currentCallResp = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/call/current-session');
        $currentCallResp->assertStatus(200);
        $currentCallResp->assertJsonPath('data.session.consumer.name', 'Firoz Khan');
        $currentCallResp->assertJsonPath('data.session.consumer.place_of_birth', 'Lucknow, Uttar Pradesh');
        $currentCallResp->assertJsonPath('data.session.consumer.time_of_birth', '15:50:00');

        // 3. Astrologer checks /api/v1/call/pending
        $pendingResp = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/call/pending');
        $pendingResp->assertStatus(200);
        $pendingResp->assertJsonPath('data.pending_calls.0.caller.name', 'Firoz Khan');
        $pendingResp->assertJsonPath('data.pending_calls.0.caller.place_of_birth', 'Lucknow, Uttar Pradesh');
    }

    public function test_chat_initiated_and_current_session_include_full_consumer_birth_details(): void
    {
        Event::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $consumer = User::factory()->create([
            'user_type'      => 'user',
            'name'           => 'Riya Sen',
            'phone'          => '9876543210',
            'gender'         => 'female',
            'date_of_birth'  => '2000-01-15',
            'time_of_birth'  => '10:30:00',
            'place_of_birth' => 'Kolkata, West Bengal',
            'latitude'       => 22.5726,
            'longitude'      => 88.3639,
            'city'           => 'Kolkata',
            'country'        => 'India',
        ]);

        Wallet::create([
            'user_id' => $consumer->id,
            'balance' => 1000.00,
        ]);

        $astrologerUser = User::factory()->create([
            'user_type' => 'astrologer',
            'name'      => 'Pandit Ji',
        ]);

        Astrologer::factory()->create([
            'user_id'              => $astrologerUser->id,
            'is_chat_enabled'      => true,
            'is_online'            => true,
            'chat_rate_per_minute' => 10.00,
            'status'               => 'approved',
        ]);

        // 1. Consumer initiates chat
        $response = $this->actingAs($consumer, 'sanctum')->postJson('/api/v1/chat/initiate', [
            'provider_id' => $astrologerUser->id,
            'question'    => 'Career guidance needed',
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(ChatInitiated::class, function ($event) {
            $payload = $event->broadcastWith();
            return isset($payload['session']['consumer']['name'])
                && $payload['session']['consumer']['name'] === 'Riya Sen'
                && $payload['senderData']['place_of_birth'] === 'Kolkata, West Bengal'
                && $payload['senderData']['time_of_birth'] === '10:30:00';
        });

        // 2. Astrologer checks /api/v1/chat/current-session during ringing/initiated
        $currentChatResp = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/chat/current-session');
        $currentChatResp->assertStatus(200);
        $currentChatResp->assertJsonPath('data.session.consumer.name', 'Riya Sen');
        $currentChatResp->assertJsonPath('data.session.consumer.place_of_birth', 'Kolkata, West Bengal');

        // 3. Astrologer checks /api/v1/chat/sessions/current
        $acceptedResp = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/chat/sessions/current');
        $acceptedResp->assertStatus(200);
        $acceptedResp->assertJsonPath('data.session.consumer.name', 'Riya Sen');
        $acceptedResp->assertJsonPath('data.session.consumer.place_of_birth', 'Kolkata, West Bengal');
    }

    public function test_package_sub_session_initiation_includes_full_consumer_birth_details(): void
    {
        Event::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $consumer = User::factory()->create([
            'user_type'      => 'user',
            'name'           => 'Aarav Patel',
            'phone'          => '9123456780',
            'gender'         => 'male',
            'date_of_birth'  => '1998-05-20',
            'time_of_birth'  => '08:15:00',
            'place_of_birth' => 'Ahmedabad, Gujarat',
        ]);

        $astrologerUser = User::factory()->create([
            'user_type' => 'astrologer',
            'name'      => 'Guru Ji',
        ]);

        Astrologer::factory()->create([
            'user_id'              => $astrologerUser->id,
            'is_chat_enabled'      => true,
            'is_call_enabled'      => true,
            'is_online'            => true,
            'status'               => 'approved',
        ]);

        $packagePurchase = \App\Models\PackagePurchase::create([
            'user_id'               => $consumer->id,
            'astrologer_id'         => $astrologerUser->id,
            'status'                => 'active',
            'total_duration'        => 1800,
            'remaining_duration'    => 1800,
            'purchase_price'        => 500.00,
            'commission_percentage' => 20.00,
            'admin_earnings'        => 100.00,
            'astrologer_earnings'   => 400.00,
        ]);

        // Start package chat sub-session
        $timerService = app(\App\Services\SessionTimerService::class);
        $result = $timerService->startSubSession($consumer->id, $astrologerUser->id, 'chat', 'Need horoscope analysis');

        Event::assertDispatched(ChatInitiated::class, function ($event) {
            $payload = $event->broadcastWith();
            return isset($payload['session']['consumer']['name'])
                && $payload['session']['consumer']['name'] === 'Aarav Patel'
                && $payload['senderData']['place_of_birth'] === 'Ahmedabad, Gujarat';
        });

        // Astrologer fetches current session
        $chatResp = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/chat/current-session');
        $chatResp->assertStatus(200);
        $chatResp->assertJsonPath('data.session.consumer.name', 'Aarav Patel');
        $chatResp->assertJsonPath('data.session.consumer.place_of_birth', 'Ahmedabad, Gujarat');
        $chatResp->assertJsonPath('data.is_prepaid', true);
    }
}

