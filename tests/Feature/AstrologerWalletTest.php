<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Astrologer;
use App\Models\AstrologerBankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AstrologerWalletTest extends TestCase
{
    use RefreshDatabase;

    private $astrologerUser;
    private $astrologerProfile;
    private $wallet;
    private $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Lock time to a clean baseline
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0, 0)); // A Monday

        // Create astrologer user
        $this->astrologerUser = User::factory()->create([
            'name' => 'Astrologer Vikram',
            'user_type' => 'astrologer'
        ]);

        // Create astrologer profile
        $this->astrologerProfile = Astrologer::create([
            'user_id' => $this->astrologerUser->id,
            'is_online' => true,
        ]);

        // Create wallet
        $this->wallet = Wallet::create([
            'user_id' => $this->astrologerUser->id,
            'balance' => 1000.00
        ]);

        // Create bank account for the astrologer
        $this->bankAccount = AstrologerBankAccount::create([
            'astrologer_id' => $this->astrologerProfile->id,
            'account_holder_name' => 'Vikram Sharma',
            'bank_name' => 'State Bank of India',
            'account_number' => '1234567890',
            'ifsc_code' => 'SBIN0001234',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function it_denies_non_astrologers_from_accessing_wallet_endpoints()
    {
        $nonAstrologer = User::factory()->create(['user_type' => 'user']);

        $response = $this->actingAs($nonAstrologer)->getJson('/api/v1/astrologer/wallet');
        $response->assertStatus(403);
    }

    /** @test */
    public function it_calculates_wallet_summary_stats_and_rank()
    {
        // Setup another astrologer to verify ranking works
        $anotherUser = User::factory()->create(['user_type' => 'astrologer']);
        $anotherProfile = Astrologer::create(['user_id' => $anotherUser->id]);
        $anotherWallet = Wallet::create(['user_id' => $anotherUser->id, 'balance' => 2000.00]);

        // Credit another astrologer with large completed earning
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $anotherWallet->id,
            'transaction_type' => 'credit',
            'amount' => 1500.00,
            'status' => 'completed',
            'description' => 'Consultation credit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Credit current astrologer with varying dates:
        // Today (amount 100)
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 100.00,
            'status' => 'completed',
            'description' => 'Today credit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Today, earlier (amount 200) -> counts for today & weekly
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 200.00,
            'status' => 'completed',
            'description' => 'Weekly credit',
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        // Earlier this month (5 days ago, June 10 -> amount 300) -> counts for monthly
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 300.00,
            'status' => 'completed',
            'description' => 'Monthly credit',
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5),
        ]);

        // 2 Months ago (April 15 -> amount 400) -> counts for 3-month
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 400.00,
            'status' => 'completed',
            'description' => '3 month credit',
            'created_at' => Carbon::now()->subMonths(2),
            'updated_at' => Carbon::now()->subMonths(2),
        ]);

        // Pending credit should be ignored
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 500.00,
            'status' => 'pending',
            'description' => 'Pending credit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Fetch summary
        $response = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet');
        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'total_balance' => 1000.00,
                'today_earning' => 300.00, // 100 today + 200 earlier today
                'weekly_earning' => 300.00, // 100 today + 200 earlier today
                'monthly_earning' => 600.00, // 300 today/weekly + 300 monthly
                'three_month_earning' => 1000.00, // 600 + 400 3-month
                'rank' => 2, // Vikram total completed: 1000, Another total completed: 1500. So rank 2.
            ]
        ]);
    }

    /** @test */
    public function it_filters_earnings_history()
    {
        // 1. Credit today
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 50.00,
            'status' => 'completed',
            'description' => 'Earning today',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Credit last week
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 150.00,
            'status' => 'completed',
            'description' => 'Earning last week',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        // Fetch all earnings
        $response = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet/earnings');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));

        // Filter today
        $responseToday = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet/earnings?filter=today');
        $responseToday->assertStatus(200);
        $this->assertCount(1, $responseToday->json('data.data'));
        $this->assertEquals('Earning today', $responseToday->json('data.data.0.description'));
    }

    /** @test */
    public function it_submits_withdrawal_request_and_validates_available_balance()
    {
        // Submit valid withdrawal of 300
        $response = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/wallet/withdraw', [
            'amount' => 300.00,
            'bank_account_id' => $this->bankAccount->id
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');

        // Check transaction in DB
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'debit',
            'amount' => 300.00,
            'status' => 'pending',
            'description' => 'Withdrawal Request',
        ]);

        // Submit another withdrawal of 800
        // Available balance is 1000 - 300 (pending) = 700. So 800 should fail.
        $responseFail = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/wallet/withdraw', [
            'amount' => 800.00,
            'bank_account_id' => $this->bankAccount->id
        ]);

        $responseFail->assertStatus(422);
        $responseFail->assertJsonPath('status', 'error');
        $responseFail->assertJsonFragment([
            'message' => 'Insufficient available balance. Your total balance is ₹1000.00, but you have ₹300 in pending withdrawals.'
        ]);
    }

    /** @test */
    public function it_retrieves_withdrawal_history()
    {
        // Create a withdrawal transaction
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'debit',
            'amount' => 150.00,
            'status' => 'pending',
            'description' => 'Withdrawal Request',
        ]);

        $response = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet/withdrawals');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Withdrawal Request', $response->json('data.data.0.description'));
    }

    /** @test */
    public function it_retrieves_weekly_rankings()
    {
        // 1. Setup another astrologer
        $anotherUser = User::factory()->create(['name' => 'Astrologer Rajesh', 'user_type' => 'astrologer']);
        $anotherProfile = Astrologer::create(['user_id' => $anotherUser->id]);
        $anotherWallet = Wallet::create(['user_id' => $anotherUser->id, 'balance' => 2000.00]);

        // Credit another astrologer with completed earning this week
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $anotherWallet->id,
            'transaction_type' => 'credit',
            'amount' => 500.00,
            'status' => 'completed',
            'description' => 'Consultation credit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Credit current astrologer with completed earning this week
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 250.00,
            'status' => 'completed',
            'description' => 'Consultation credit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Credit current astrologer with completed earning from LAST week (should be ignored)
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => 'credit',
            'amount' => 1000.00,
            'status' => 'completed',
            'description' => 'Consultation credit old',
            'created_at' => Carbon::now()->subWeek(),
            'updated_at' => Carbon::now()->subWeek(),
        ]);

        $response = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet/weekly-rankings');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'data' => [
                'top_astrologers',
                'my_rank',
                'my_weekly_earnings',
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['my_rank']); // Rajesh is #1 with 500, Vikram is #2 with 250
        $this->assertEquals(250.00, $data['my_weekly_earnings']);
        $this->assertCount(2, $data['top_astrologers']);
        $this->assertEquals('Astrologer Rajesh', $data['top_astrologers'][0]['name']);
        $this->assertEquals(500.00, $data['top_astrologers'][0]['weekly_earnings']);
        $this->assertEquals('Astrologer Vikram', $data['top_astrologers'][1]['name']);
        $this->assertEquals(250.00, $data['top_astrologers'][1]['weekly_earnings']);
    }
}
