<?php

namespace Tests\Feature;

use App\Events\CallEnded;
use App\Jobs\CallBillingTickJob;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CallService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CallStabilityAndTurnTransportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astrologerUser;
    protected Astrologer $astrologer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['user_type' => 'user']);
        $this->astrologerUser = User::factory()->create(['user_type' => 'astrologer']);

        $this->astrologer = Astrologer::create([
            'user_id' => $this->astrologerUser->id,
            'display_name' => 'Pt. Anand Shastri',
            'is_call_enabled' => true,
            'call_rate_per_minute' => 20.00,
            'astrologer_share_percentage' => 70,
        ]);
    }

    /** @test */
    public function turn_credentials_api_returns_multi_transport_udp_and_tcp_urls_for_static_coturn()
    {
        config([
            'services.turn.server_url' => 'turn:187.127.173.87:3478',
            'services.turn.username' => 'livekit',
            'services.turn.credential' => 'livekit_secret_2024',
            'services.turn.secret' => null,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $iceServers = $response->json('data.iceServers');
        $this->assertNotEmpty($iceServers);

        // Verify STUN server
        $stun = collect($iceServers)->firstWhere('urls', 'stun:stun.l.google.com:19302');
        $this->assertNotNull($stun);

        // Verify UDP TURN URL
        $turnUdp = collect($iceServers)->firstWhere('urls', 'turn:187.127.173.87:3478');
        $this->assertNotNull($turnUdp);
        $this->assertEquals('livekit', $turnUdp['username']);
        $this->assertEquals('livekit_secret_2024', $turnUdp['credential']);

        // Verify TCP TURN URL (for mobile carrier bypass)
        $turnTcp = collect($iceServers)->firstWhere('urls', 'turn:187.127.173.87:3478?transport=tcp');
        $this->assertNotNull($turnTcp);
        $this->assertEquals('livekit', $turnTcp['username']);
        $this->assertEquals('livekit_secret_2024', $turnTcp['credential']);
    }

    /** @test */
    public function call_billing_tick_job_successfully_bills_minute_when_balance_sufficient()
    {
        Wallet::create(['user_id' => $this->user->id, 'balance' => 100.00]);
        Wallet::create(['user_id' => $this->astrologerUser->id, 'balance' => 0.00]);

        $session = CallSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astrologerUser->id,
            'status' => 'ongoing',
            'rate_per_minute' => 20.00,
            'started_at' => now()->subMinute(),
            'last_billed_at' => now()->subMinute(),
            'total_cost' => 0.00,
        ]);

        $job = new CallBillingTickJob($session->id);
        $job->handle(app(WalletService::class), app(CallService::class));

        $session->refresh();
        $this->assertEquals('ongoing', $session->status);
        $this->assertEquals(20.00, $session->total_cost);

        $consumerWallet = Wallet::where('user_id', $this->user->id)->first();
        $this->assertEquals(80.00, $consumerWallet->balance);

        $providerWallet = Wallet::where('user_id', $this->astrologerUser->id)->first();
        $this->assertEquals(16.00, $providerWallet->balance); // 80% (100% - 20% global admin rate) of 20
    }

    /** @test */
    public function call_billing_tick_job_gracefully_ends_call_only_when_balance_is_insufficient()
    {
        Event::fake([CallEnded::class]);

        // User has only 10.00 balance, but rate is 20.00
        Wallet::create(['user_id' => $this->user->id, 'balance' => 10.00]);
        Wallet::create(['user_id' => $this->astrologerUser->id, 'balance' => 0.00]);

        $session = CallSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astrologerUser->id,
            'status' => 'ongoing',
            'rate_per_minute' => 20.00,
            'started_at' => now()->subMinute(),
            'last_billed_at' => now()->subMinute(),
            'total_cost' => 0.00,
        ]);

        $job = new CallBillingTickJob($session->id);
        $job->handle(app(WalletService::class), app(CallService::class));

        $session->refresh();
        $this->assertEquals('completed', $session->status);

        Event::assertDispatched(CallEnded::class);
    }
}
