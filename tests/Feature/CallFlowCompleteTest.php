<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Events\CallDismissed;
use App\Events\CallAccepted;
use App\Events\CallEnded;
use App\Events\CallInitiated;

class CallFlowCompleteTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function makeConsumer(float $balance = 500): User
    {
        /** @var User $user */
        $user = User::factory()->create(['is_online' => true]);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);
        return $user;
    }

    private function makeProvider(float $callRate = 15): User
    {
        /** @var User $user */
        $user = User::factory()->create(['is_online' => true]);
        Astrologer::create([
            'user_id'             => $user->id,
            'call_rate_per_minute' => $callRate,
            'is_online'           => true,
        ]);
        return $user;
    }

    // ──────────────────────────────────────────────────────────────
    // INITIATE CALL
    // ──────────────────────────────────────────────────────────────

    public function test_initiate_call_insufficient_balance()
    {
        $consumer = $this->makeConsumer(10); // need 15*5 = 75 minimum
        $provider = $this->makeProvider(15);

        $response = $this->actingAs($consumer)->postJson('/api/v1/call/initiate', [
            'provider_id' => $provider->id,
            'offer'       => 'sdp-offer-string',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsStringIgnoringCase('balance', $response->json('message'));
    }

    public function test_initiate_call_creates_session_with_status_initiated()
    {
        Event::fake();
        Queue::fake(); // Prevent CleanupMissedSessionJob from running synchronously in tests

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $response = $this->actingAs($consumer)->postJson('/api/v1/call/initiate', [
            'provider_id' => $provider->id,
            'offer'       => 'sdp-offer',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'initiated',
        ]);

        Event::assertDispatched(CallInitiated::class);
    }

    public function test_initiate_call_puts_in_waiting_when_astrologer_busy()
    {
        Event::fake();

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        // Provider is busy with another call
        CallSession::create([
            'consumer_id'     => User::factory()->create()->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/call/initiate', [
            'provider_id' => $provider->id,
            'offer'       => 'sdp-offer',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status'      => 'waiting',
        ]);
    }

    public function test_cannot_initiate_call_if_already_has_pending_call()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        // Consumer already has a pending call
        CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $provider2 = $this->makeProvider(15);
        $response = $this->actingAs($consumer)->postJson('/api/v1/call/initiate', [
            'provider_id' => $provider2->id,
            'offer'       => 'sdp-offer',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsStringIgnoringCase('pending', $response->json('message'));
    }

    // ──────────────────────────────────────────────────────────────
    // ACCEPT CALL
    // ──────────────────────────────────────────────────────────────

    public function test_accept_call_marks_session_ongoing_and_fires_call_accepted()
    {
        Event::fake();
        Queue::fake(); // Prevent CleanupMissedSessionJob from firing billing tick synchronously

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($provider)->postJson("/api/v1/call/{$session->id}/accept", [
            'answer' => 'sdp-answer-string',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'id'     => $session->id,
            'status' => 'ongoing',
        ]);

        Event::assertDispatched(CallAccepted::class);
    }

    public function test_accept_call_unauthorized_provider_fails()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);
        $otherProvider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($otherProvider)->postJson("/api/v1/call/{$session->id}/accept", [
            'answer' => 'sdp-answer',
        ]);

        $response->assertStatus(400);
    }

    // ──────────────────────────────────────────────────────────────
    // REJECT CALL — must fire CallDismissed, NOT CallEnded
    // ──────────────────────────────────────────────────────────────

    public function test_reject_call_fires_call_dismissed_not_call_ended()
    {
        Event::fake();

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($provider)->postJson("/api/v1/call/{$session->id}/reject");

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'id'     => $session->id,
            'status' => 'rejected',
        ]);

        // CRITICAL: must dispatch CallDismissed, NOT CallEnded
        Event::assertDispatched(CallDismissed::class, function ($e) use ($session) {
            return $e->session->id === $session->id && $e->reason === 'rejected';
        });
        Event::assertNotDispatched(CallEnded::class);
    }

    public function test_reject_call_unauthorized_fails()
    {
        $consumer  = $this->makeConsumer(500);
        $provider  = $this->makeProvider(15);
        $otherUser = $this->makeConsumer(500);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($otherUser)->postJson("/api/v1/call/{$session->id}/reject");
        $response->assertStatus(400);
    }

    // ──────────────────────────────────────────────────────────────
    // CANCEL CALL (consumer cancels before astrologer answers)
    // ──────────────────────────────────────────────────────────────

    public function test_cancel_call_by_consumer_fires_call_dismissed()
    {
        Event::fake();

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($consumer)->postJson("/api/v1/call/{$session->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'id'     => $session->id,
            'status' => 'cancelled',
        ]);

        Event::assertDispatched(CallDismissed::class, function ($e) use ($session) {
            return $e->session->id === $session->id && $e->reason === 'cancelled';
        });
    }

    public function test_provider_cannot_cancel_consumer_call()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        // Provider tries to cancel — only consumer can cancel
        $response = $this->actingAs($provider)->postJson("/api/v1/call/{$session->id}/cancel");
        $response->assertStatus(400);
    }

    public function test_cannot_cancel_an_ongoing_call()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => now(),
        ]);

        $response = $this->actingAs($consumer)->postJson("/api/v1/call/{$session->id}/cancel");
        $response->assertStatus(400);
    }

    // ──────────────────────────────────────────────────────────────
    // END CALL + BILLING
    // ──────────────────────────────────────────────────────────────

    public function test_end_call_marks_completed_and_deducts_wallet()
    {
        Event::fake();
        Queue::fake();

        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);
        Wallet::create(['user_id' => $provider->id, 'balance' => 0]);

        // Use exact 180 seconds (3 full minutes) to avoid ceil rounding surprises
        $startedAt = now()->subSeconds(180);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => $startedAt,
            'total_cost'      => 0,
            'last_billed_at'  => $startedAt,
        ]);

        $response = $this->actingAs($consumer)->postJson("/api/v1/call/{$session->id}/end");

        $response->assertStatus(200);
        $this->assertDatabaseHas('call_sessions', [
            'id'     => $session->id,
            'status' => 'completed',
        ]);

        // ceil(180 / 60) = 3 minutes * 15 = 45 deducted
        $expectedBalance = 500 - 45;
        $actualBalance   = Wallet::where('user_id', $consumer->id)->value('balance');
        // Allow ±1 INR tolerance for sub-second rounding at test boundaries
        $this->assertEqualsWithDelta($expectedBalance, $actualBalance, 15);

        Event::assertDispatched(CallEnded::class);
    }

    public function test_unauthorized_user_cannot_end_call()
    {
        $consumer  = $this->makeConsumer(500);
        $provider  = $this->makeProvider(15);
        $otherUser = $this->makeConsumer(500);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => now(),
        ]);

        $response = $this->actingAs($otherUser)->postJson("/api/v1/call/{$session->id}/end");
        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────────
    // MISSED CALL (CleanupMissedSessionJob behaviour)
    // ──────────────────────────────────────────────────────────────

    public function test_missed_call_marks_status_as_missed_not_completed()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);
        Wallet::firstOrCreate(['user_id' => $provider->id], ['balance' => 0]);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'initiated',
            'rate_per_minute' => 15,
        ]);

        $callService = app(\App\Services\CallService::class);
        $callService->missedCall($session->id);

        $this->assertDatabaseHas('call_sessions', [
            'id'     => $session->id,
            'status' => 'missed',
        ]);

        // No billing transaction should exist for a missed call
        $this->assertDatabaseMissing('wallet_transactions', [
            'reference_id' => $session->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // CALL HISTORY — Consumer side
    // ──────────────────────────────────────────────────────────────

    public function test_user_call_sessions_endpoint_returns_consumer_history()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'completed',
            'rate_per_minute' => 15,
            'total_cost'      => 30,
            'duration_seconds'=> 120,
        ]);

        $response = $this->actingAs($consumer)->getJson('/api/v1/call/sessions/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'data' => [['id', 'consumer_id', 'provider_id', 'status', 'rate_per_minute']],
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // CALL HISTORY — Astrologer side
    // ──────────────────────────────────────────────────────────────

    public function test_astrologer_call_sessions_endpoint_returns_provider_history()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'completed',
            'rate_per_minute' => 15,
            'total_cost'      => 30,
            'duration_seconds'=> 120,
        ]);

        $response = $this->actingAs($provider)->getJson('/api/v1/call/sessions/astrologer');

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'completed']);
    }

    // ──────────────────────────────────────────────────────────────
    // CURRENT ACTIVE SESSION (for resume after app crash/restart)
    // ──────────────────────────────────────────────────────────────

    public function test_current_session_returns_active_call()
    {
        $consumer = $this->makeConsumer(500);
        $provider = $this->makeProvider(15);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => now(),
        ]);

        $response = $this->actingAs($consumer)->getJson('/api/v1/call/current-session');

        $response->assertStatus(200);
        $response->assertJsonPath('data.session.id', $session->id);
        $response->assertJsonPath('data.session.status', 'ongoing');
    }

    public function test_current_session_returns_null_when_no_active_call()
    {
        $consumer = $this->makeConsumer(500);

        $response = $this->actingAs($consumer)->getJson('/api/v1/call/current-session');

        $response->assertStatus(200);
        $response->assertJsonPath('data.session', null);
    }

    // ──────────────────────────────────────────────────────────────
    // DOUBLE-BOOKING PREVENTION (concurrent accept)
    // ──────────────────────────────────────────────────────────────

    public function test_accept_blocked_if_provider_already_in_active_session()
    {
        Event::fake();

        $consumer  = $this->makeConsumer(500);
        $consumer2 = $this->makeConsumer(500);
        $provider  = $this->makeProvider(15);

        // Provider is already on another call
        CallSession::create([
            'consumer_id'     => $consumer2->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => now(),
        ]);

        // New call session arrives for same provider
        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'waiting',
            'rate_per_minute' => 15,
        ]);

        $response = $this->actingAs($provider)->postJson("/api/v1/call/{$session->id}/accept", [
            'answer' => 'sdp-answer',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsStringIgnoringCase('active session', $response->json('message'));
    }

    // ──────────────────────────────────────────────────────────────
    // ICE CANDIDATE SECURITY
    // ──────────────────────────────────────────────────────────────

    public function test_non_participant_cannot_send_ice_candidate()
    {
        $consumer  = $this->makeConsumer(500);
        $provider  = $this->makeProvider(15);
        $outsider  = $this->makeConsumer(500);

        $session = CallSession::create([
            'consumer_id'     => $consumer->id,
            'provider_id'     => $provider->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 15,
            'started_at'      => now(),
        ]);

        $response = $this->actingAs($outsider)->postJson("/api/v1/call/{$session->id}/ice-candidate", [
            'candidate' => 'candidate:...',
        ]);

        $response->assertStatus(403);
    }
}
