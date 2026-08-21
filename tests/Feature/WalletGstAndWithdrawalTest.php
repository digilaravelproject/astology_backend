<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Astrologer;
use App\Models\AstrologerBankAccount;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\RazorpayService;
use Mockery;

class WalletGstAndWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $astrologerUser;
    private Astrologer $astrologerProfile;
    private Wallet $astrologerWallet;
    private AstrologerBankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Configure settings for GST
        Setting::set('gst_enabled', true, 'boolean', 'tax');
        Setting::set('gst_recharge_enabled', true, 'boolean', 'tax');
        Setting::set('gst_withdrawal_enabled', true, 'boolean', 'tax');
        Setting::set('gst_recharge_rate', 18.00, 'decimal', 'tax');
        Setting::set('gst_withdrawal_rate', 18.00, 'decimal', 'tax');
        Setting::set('min_withdrawal_amount', 100.00, 'decimal', 'wallet');
        Setting::set('min_withdrawal_gst_threshold', 0.00, 'decimal', 'tax');
        Setting::set('min_wallet_recharge', 50.00, 'decimal', 'wallet');
        Setting::set('company_name', 'Astology Test Pvt Ltd', 'string', 'tax');
        Setting::set('company_gstin', '07AAAAA0000A1Z5', 'string', 'tax');

        // 2. Setup regular consumer
        $this->user = User::factory()->create([
            'name' => 'Rahul Sharma',
            'email' => 'rahul@example.com',
            'user_type' => 'user',
        ]);

        // 3. Setup astrologer user and profile
        $this->astrologerUser = User::factory()->create([
            'name' => 'Pandit Dev',
            'email' => 'pandit.dev@example.com',
            'user_type' => 'astrologer',
        ]);

        $this->astrologerProfile = Astrologer::create([
            'user_id' => $this->astrologerUser->id,
            'name' => 'Pandit Dev',
            'email' => 'pandit.dev@example.com',
            'phone' => '9876543210',
            'experience_years' => 10,
            'is_online' => true,
        ]);

        $this->astrologerWallet = Wallet::create([
            'user_id' => $this->astrologerUser->id,
            'balance' => 5000.00,
        ]);

        $this->bankAccount = AstrologerBankAccount::create([
            'astrologer_id' => $this->astrologerProfile->id,
            'account_holder_name' => 'Pandit Dev',
            'bank_name' => 'HDFC Bank',
            'account_number' => '987654321098',
            'ifsc_code' => 'HDFC0001234',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_initiate_wallet_topup_with_18_percent_gst_breakdown()
    {
        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('createOrder')
            ->once()
            ->with(11800, 'INR', Mockery::type('string'), Mockery::type('array'))
            ->andReturn([
                'status' => 'success',
                'data' => [
                    'id' => 'order_gst_100',
                    'amount' => 11800,
                    'currency' => 'INR',
                ],
            ]);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/topup', [
            'amount' => 100.00,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'pricing_breakdown' => [
                    'base_amount' => 100.0,
                    'gst_enabled' => true,
                    'gst_percent' => 18.0,
                    'gst_amount' => 18.0,
                    'total_payable' => 118.0,
                    'wallet_credit_amount' => 100.0,
                ],
            ],
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'amount' => 100.00,
            'base_amount' => 100.00,
            'gst_percent' => 18.00,
            'gst_amount' => 18.00,
            'total_amount' => 118.00,
            'status' => 'pending',
            'provider_order_id' => 'order_gst_100',
        ]);
    }

    /** @test */
    public function user_verify_topup_credits_base_amount_and_generates_tax_invoice()
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $this->user->id], ['balance' => 0.00]);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => 100.00,
            'base_amount' => 100.00,
            'gst_percent' => 18.00,
            'gst_amount' => 18.00,
            'total_amount' => 118.00,
            'status' => 'pending',
            'payment_provider' => 'razorpay',
            'provider_order_id' => 'order_gst_100',
            'description' => 'Wallet top-up (pending payment)',
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifySignature')
            ->once()
            ->with('order_gst_100', 'pay_gst_100', 'valid_sig_100')
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/topup/verify', [
            'razorpay_order_id' => 'order_gst_100',
            'razorpay_payment_id' => 'pay_gst_100',
            'razorpay_signature' => 'valid_sig_100',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Wallet credited with base amount ONLY
        $wallet->refresh();
        $this->assertEquals(100.00, $wallet->balance);

        // Transaction marked completed with invoice number
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertNotNull($transaction->invoice_number);
        $this->assertStringStartsWith('INV-REC-' . date('Y'), $transaction->invoice_number);
    }

    /** @test */
    public function user_can_download_tax_invoice_pdf()
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $this->user->id], ['balance' => 100.00]);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => 100.00,
            'base_amount' => 100.00,
            'gst_percent' => 18.00,
            'gst_amount' => 18.00,
            'total_amount' => 118.00,
            'invoice_number' => 'INV-REC-20260821-000001',
            'status' => 'completed',
            'payment_provider' => 'razorpay',
            'provider_payment_id' => 'pay_123',
            'description' => 'Wallet top-up',
        ]);

        $response = $this->actingAs($this->user)->get("/api/v1/user/wallet/transactions/{$transaction->id}/invoice");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    /** @test */
    public function astrologer_can_update_profile_with_valid_gstin()
    {
        $response = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/profile', [
            'name' => 'Pandit Dev Sharma',
            'gst_number' => '07aaaaa0000a1z5', // Lowercase should be normalized
        ]);

        $response->assertStatus(200);
        $this->astrologerProfile->refresh();
        $this->assertEquals('07AAAAA0000A1Z5', $this->astrologerProfile->gst_number);
    }

    /** @test */
    public function astrologer_profile_rejects_invalid_gstin_format()
    {
        $response = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/profile', [
            'name' => 'Pandit Dev Sharma',
            'gst_number' => 'INVALID_GST_123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gst_number']);
    }

    /** @test */
    public function astrologer_can_fetch_withdrawal_config_and_dynamic_tax_limits()
    {
        $response = $this->actingAs($this->astrologerUser)->getJson('/api/v1/astrologer/wallet/withdrawal-config');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'total_balance' => 5000.0,
                'pending_withdrawals' => 0.0,
                'available_balance' => 5000.0,
                'min_withdrawal_amount' => 100.0,
                'max_withdrawal_amount' => 5000.0,
                'gst_enabled' => true,
                'gst_withdrawal_rate' => 18.0,
                'min_withdrawal_gst_threshold' => 0.0,
            ],
        ]);
    }

    /** @test */
    public function astrologer_withdrawal_calculates_gst_deduction_and_creates_pending_payout()
    {
        // Astrologer requests ₹1180 withdrawal (Gross)
        // 18% GST: Base payout = 1180 / 1.18 = 1000.00, GST = 180.00
        $response = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/wallet/withdraw', [
            'amount' => 1180.00,
            'bank_account_id' => $this->bankAccount->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');

        $data = $response->json('data');
        $this->assertEquals(1180.00, $data['tax_breakdown']['total_debited']);
        $this->assertEquals(180.00, $data['tax_breakdown']['gst_amount']);
        $this->assertEquals(1000.00, $data['tax_breakdown']['base_amount']);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->astrologerWallet->id,
            'transaction_type' => 'debit',
            'amount' => 1180.00,
            'base_amount' => 1000.00,
            'gst_percent' => 18.00,
            'gst_amount' => 180.00,
            'total_amount' => 1180.00,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function astrologer_can_download_withdrawal_tax_advice_receipt_pdf()
    {
        $transaction = WalletTransaction::create([
            'wallet_id' => $this->astrologerWallet->id,
            'transaction_type' => 'debit',
            'amount' => 1180.00,
            'base_amount' => 1000.00,
            'gst_percent' => 18.00,
            'gst_amount' => 180.00,
            'total_amount' => 1180.00,
            'invoice_number' => 'INV-WD-20260821-000002',
            'status' => 'pending',
            'description' => 'Withdrawal Request',
        ]);

        $response = $this->actingAs($this->astrologerUser)->get("/api/v1/astrologer/wallet/withdrawals/{$transaction->id}/receipt");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    /** @test */
    public function astrologer_withdrawal_blocks_overdrawing_beyond_available_balance()
    {
        // 1. Create a pending withdrawal of ₹4000
        $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/wallet/withdraw', [
            'amount' => 4000.00,
            'bank_account_id' => $this->bankAccount->id,
        ])->assertStatus(201);

        // Available balance is now 5000 - 4000 = 1000.
        // Attempting to withdraw ₹1500 must fail with 422
        $failResponse = $this->actingAs($this->astrologerUser)->postJson('/api/v1/astrologer/wallet/withdraw', [
            'amount' => 1500.00,
            'bank_account_id' => $this->bankAccount->id,
        ]);

        $failResponse->assertStatus(422);
        $failResponse->assertJsonPath('status', 'error');
        $this->assertStringContainsString('Insufficient available balance', $failResponse->json('message'));
    }

    /** @test */
    public function admin_can_update_gst_and_tax_settings()
    {
        $admin = \App\Models\Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@astology.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post('/admin/settings', [
            'gst_enabled' => '1',
            'gst_recharge_enabled' => '1',
            'gst_withdrawal_enabled' => '1',
            'gst_recharge_rate' => '18.00',
            'gst_withdrawal_rate' => '12.00',
            'min_withdrawal_gst_threshold' => '500.00',
            'min_wallet_recharge' => '100.00',
            'min_withdrawal_amount' => '250.00',
            'company_name' => 'Astro Legal Tech LLP',
            'company_gstin' => '27ABCDE1234F1Z5',
            'company_pan' => 'ABCDE1234F',
            'company_address' => 'Bandra Kurla Complex, Mumbai, Maharashtra 400051',
            'company_state' => 'Maharashtra',
            'company_state_code' => '27',
        ]);

        $response->assertRedirect();

        $this->assertEquals(12.00, (float)Setting::get('gst_withdrawal_rate'));
        $this->assertEquals(500.00, (float)Setting::get('min_withdrawal_gst_threshold'));
        $this->assertEquals('Astro Legal Tech LLP', Setting::get('company_name'));
        $this->assertEquals('27ABCDE1234F1Z5', Setting::get('company_gstin'));
    }

    /** @test */
    public function admin_can_download_wallet_transaction_tax_invoice_pdf()
    {
        $admin = \App\Models\Admin::create([
            'name' => 'Super Admin 2',
            'email' => 'admin2@astology.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $transaction = \App\Models\WalletTransaction::create([
            'wallet_id' => $this->astrologerWallet->id,
            'transaction_type' => 'credit',
            'amount' => 500.00,
            'base_amount' => 500.00,
            'gst_percent' => 18.00,
            'gst_amount' => 90.00,
            'total_amount' => 590.00,
            'invoice_number' => 'INV-REC-20260821-000001',
            'status' => 'completed',
            'payment_provider' => 'razorpay',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/wallet-transactions/' . $transaction->id . '/invoice');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
