<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AstrologerInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_fetches_astrologer_invoices_summary_and_list()
    {
        // 1. Create User/Astrologer
        $user = User::factory()->create(['user_type' => 'astrologer']);
        \App\Models\Astrologer::create(['user_id' => $user->id]);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 1000.00]);

        // 2. Create some credit earnings transactions
        // January 2026 transactions
        \Illuminate\Support\Facades\DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => 45403.76,
            'status' => 'completed',
            'description' => 'Chat Session Earnings',
            'created_at' => Carbon::create(2026, 1, 15, 12, 0, 0),
            'updated_at' => Carbon::create(2026, 1, 15, 12, 0, 0),
        ]);

        // December 2025 transactions
        \Illuminate\Support\Facades\DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => 32100.00,
            'status' => 'completed',
            'description' => 'Call Session Earnings',
            'created_at' => Carbon::create(2025, 12, 10, 12, 0, 0),
            'updated_at' => Carbon::create(2025, 12, 10, 12, 0, 0),
        ]);

        // Create a withdrawal/debit transaction
        \Illuminate\Support\Facades\DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'amount' => 20000.00,
            'status' => 'completed',
            'description' => 'Withdrawal Request payout',
            'created_at' => Carbon::create(2026, 1, 20, 12, 0, 0),
            'updated_at' => Carbon::create(2026, 1, 20, 12, 0, 0),
        ]);

        // 3. Request API
        $response = $this->actingAs($user)->getJson('/api/v1/astrologer/wallet/invoices');

        // 4. Assert response structure & data
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'total_earnings',
                'total_withdrawn',
                'total_invoices',
                'status',
                'current_month' => [
                    'month_name',
                    'gross_earnings',
                    'net_payable',
                    'status',
                ],
                'invoices' => [
                    '*' => [
                        'month_name',
                        'gross_earnings',
                        'net_payable',
                        'status',
                        'download_url',
                    ]
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(77503.76, $data['total_earnings']);
        $this->assertEquals(20000.00, $data['total_withdrawn']);
        $this->assertEquals(2, $data['total_invoices']);

        // Check January 2026 invoice details
        $janInvoice = collect($data['invoices'])->firstWhere('month_name', 'January 2026');
        $this->assertNotNull($janInvoice);
        $this->assertEquals(45403.76, $janInvoice['gross_earnings']);
        $this->assertEquals(45403.76, $janInvoice['net_payable']);
        $this->assertEquals('Paid', $janInvoice['status']);
        $this->assertStringContainsString('/api/v1/astrologer/wallet/invoices/2026/01/download', $janInvoice['download_url']);

        // 5. Test Download Route
        $downloadResponse = $this->actingAs($user)->get("/api/v1/astrologer/wallet/invoices/2026/01/download");
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('Gross Earnings: INR 45,403.76', $downloadResponse->getContent());
    }
}
