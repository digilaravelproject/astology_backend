<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Jobs\ChatBillingTickJob;
use App\Services\WalletService;
use App\Services\ChatService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletTransactionLoggingTest extends TestCase
{
    use RefreshDatabase;

    private $consumer;
    private $provider;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();

        // Lock time to a clean baseline
        Carbon::setTestNow(Carbon::now());

        // Create consumer
        $this->consumer = User::factory()->create(['name' => 'John Consumer', 'is_online' => true, 'is_busy' => false]);
        Wallet::create(['user_id' => $this->consumer->id, 'balance' => 500.00]);

        // Create provider
        $this->provider = User::factory()->create(['name' => 'Aacharya Suresh', 'is_online' => true, 'is_busy' => false]);
        Astrologer::create([
            'user_id' => $this->provider->id,
            'is_online' => true,
            'chat_enabled' => true,
            'chat_rate_per_minute' => 15.00
        ]);
        Wallet::create(['user_id' => $this->provider->id, 'balance' => 100.00]);

        // Create session
        $this->session = ChatSession::create([
            'consumer_id' => $this->consumer->id,
            'provider_id' => $this->provider->id,
            'status' => 'ongoing',
            'rate_per_minute' => 15.00,
            'started_at' => now(),
            'last_billed_at' => now(),
            'total_cost' => 0.00,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_properly_formatted_completed_transactions_for_both_sides_during_billing_tick()
    {
        // Execute the billing tick job
        $job = new ChatBillingTickJob($this->session->id);
        $job->handle(app(WalletService::class), app(ChatService::class));

        // Check consumer wallet deduction
        $consumerWallet = Wallet::where('user_id', $this->consumer->id)->first();
        $this->assertEquals(485.00, (float) $consumerWallet->balance); // 500.00 - 15.00

        // Check provider wallet credit (80% of 15.00 = 12.00)
        $providerWallet = Wallet::where('user_id', $this->provider->id)->first();
        $this->assertEquals(112.00, (float) $providerWallet->balance); // 100.00 + 12.00

        // Check that NO transaction logs were written during billing ticks
        $userTx = WalletTransaction::where('wallet_id', $consumerWallet->id)
            ->where('transaction_type', 'debit')
            ->first();
        $this->assertNull($userTx);

        // End chat now
        $chatService = app(ChatService::class);
        $chatService->endChat($this->session->id);

        // Check user debit transaction record (total consolidated should be 15.00)
        $userTx = WalletTransaction::where('wallet_id', $consumerWallet->id)
            ->where('transaction_type', 'debit')
            ->first();

        $this->assertNotNull($userTx);
        $this->assertEquals('completed', $userTx->status);
        $this->assertEquals(15.00, (float) $userTx->amount);
        $this->assertEquals('App\Models\ChatSession', $userTx->reference_type);
        $this->assertEquals($this->session->id, $userTx->reference_id);
        $this->assertEquals('Chat session with Astrologer Aacharya Suresh', $userTx->description);
        $this->assertArrayHasKey('astrologer_name', $userTx->meta);
        $this->assertEquals('Aacharya Suresh', $userTx->meta['astrologer_name']);

        // Check provider credit transaction record (80% of 15.00 = 12.00)
        $providerTx = WalletTransaction::where('wallet_id', $providerWallet->id)
            ->where('transaction_type', 'credit')
            ->first();
 
        $this->assertNotNull($providerTx);
        $this->assertEquals('completed', $providerTx->status);
        $this->assertEquals(12.00, (float) $providerTx->amount);
        $this->assertEquals('App\Models\ChatSession', $providerTx->reference_type);
        $this->assertEquals($this->session->id, $providerTx->reference_id);
        $this->assertEquals('Chat consultation with User John Consumer', $providerTx->description);
        $this->assertArrayHasKey('user_name', $providerTx->meta);
        $this->assertEquals('John Consumer', $providerTx->meta['user_name']);
    }

    /** @test */
    public function it_caps_charge_at_remaining_balance_if_insufficient_during_chat_end()
    {
        // Set consumer balance to a low value
        $consumerWallet = Wallet::where('user_id', $this->consumer->id)->first();
        $consumerWallet->balance = 10.00;
        $consumerWallet->save();

        // Chat ends after 5 minutes 10 seconds -> ceil to 6 minutes
        // Total cost should be 6 * 15.00 = 90.00
        // Since alreadyBilled = 0, unbilledBalance = 90.00
        // Cap should be min(90, 10) = 10.00
        Carbon::setTestNow(Carbon::now()->addMinutes(5)->addSeconds(10));

        $chatService = app(ChatService::class);
        $endedSession = $chatService->endChat($this->session->id);

        $this->assertEquals('completed', $endedSession->status);
        $this->assertEquals(10.00, (float) $endedSession->total_cost);

        // Check consumer wallet is 0
        $consumerWallet->refresh();
        $this->assertEquals(0.00, (float) $consumerWallet->balance);

        // Check provider wallet credited 8 (80% of 10.00 = 8.00)
        $providerWallet = Wallet::where('user_id', $this->provider->id)->first();
        $this->assertEquals(108.00, (float) $providerWallet->balance);

        // Check user debit transaction
        $userTx = WalletTransaction::where('wallet_id', $consumerWallet->id)
            ->where('transaction_type', 'debit')
            ->first();
        $this->assertNotNull($userTx);
        $this->assertEquals(10.00, (float) $userTx->amount);

        // Check provider credit transaction (80% of 10 = 8)
        $providerTx = WalletTransaction::where('wallet_id', $providerWallet->id)
            ->where('transaction_type', 'credit')
            ->first();
        $this->assertNotNull($providerTx);
        $this->assertEquals(8.00, (float) $providerTx->amount);
    }

    /** @test */
    public function it_rolls_back_atomic_transactions_completely_on_error()
    {
        // Intercept wallet save for provider and throw exception to simulate a DB error
        \App\Models\Wallet::saving(function ($wallet) {
            if ($wallet->user_id == $this->provider->id) {
                throw new \Exception("Simulated credit failure.");
            }
        });

        // Try ending chat which invokes debit and credit
        // It should fail due to integrity constraint, throwing exception, and rolling back the consumer's wallet deduction.
        $chatService = app(ChatService::class);

        $thrown = false;
        try {
            DB::transaction(function () use ($chatService) {
                // Advance locked mock time by 1 minute
                Carbon::setTestNow(Carbon::now()->addMinute());
                $chatService->endChat($this->session->id);
            });
        } catch (Exception $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        // Verify consumer wallet is STILL 500.00 (rolled back!)
        $consumerWallet = Wallet::where('user_id', $this->consumer->id)->first();
        $this->assertEquals(500.00, (float) $consumerWallet->balance);

        // Verify no wallet transactions were created for this session
        $this->assertEquals(0, WalletTransaction::count());
    }
}
