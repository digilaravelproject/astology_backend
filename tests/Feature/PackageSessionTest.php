<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Astrologer;
use App\Models\Package;
use App\Models\AstrologerPackage;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\Setting;
use App\Jobs\TerminatePackageSessionJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class PackageSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup a global default package
        Package::create([
            'name' => 'Default 30 Mins Pack',
            'default_amount' => 50.00,
            'default_duration' => 1800, // 30 minutes in seconds
            'is_default' => true,
        ]);
        
        Setting::set('global_package_commission_rate', 50.00);
    }

    public function test_astrologer_observer_assigns_default_package_on_creation()
    {
        $user = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'years_of_experience' => 5,
            'languages' => ['English'],
            'id_proof_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('astrologer_packages', [
            'astrologer_id' => $user->id,
            'amount' => 50.00,
            'duration' => 1800,
        ]);
    }

    public function test_purchase_package_insufficient_balance()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 10.00]); // Package costs 50.00

        $provider = User::factory()->create(['user_type' => 'astrologer']);
        Astrologer::create([
            'user_id' => $provider->id,
            'years_of_experience' => 5,
            'languages' => ['English'],
            'id_proof_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/packages/purchase', [
            'astrologer_id' => $provider->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');
    }

    public function test_purchase_package_success_and_ledger_splits()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        Wallet::create(['user_id' => $consumer->id, 'balance' => 100.00]);

        $provider = User::factory()->create(['user_type' => 'astrologer']);
        Astrologer::create([
            'user_id' => $provider->id,
            'years_of_experience' => 5,
            'languages' => ['English'],
            'id_proof_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'status' => 'approved',
        ]);

        // Create override commission percentage for this astrologer
        AstrologerPackage::where('astrologer_id', $provider->id)->update([
            'commission_percentage' => 60.00 // 60% goes to astrologer (30.00), 40% to admin (20.00)
        ]);

        $response = $this->actingAs($consumer)->postJson('/api/v1/packages/purchase', [
            'astrologer_id' => $provider->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Verify package purchase record
        $this->assertDatabaseHas('package_purchases', [
            'user_id' => $consumer->id,
            'astrologer_id' => $provider->id,
            'total_duration' => 1800,
            'remaining_duration' => 1800,
            'purchase_price' => 50.00,
            'commission_percentage' => 60.00,
            'astrologer_earnings' => 30.00,
            'admin_earnings' => 20.00,
            'status' => 'active',
        ]);

        // Verify wallet balances
        $this->assertEquals(50.00, Wallet::where('user_id', $consumer->id)->value('balance')); // 100 - 50 = 50
        $this->assertEquals(30.00, Wallet::where('user_id', $provider->id)->value('balance')); // 0 + 30 = 30

        // Verify wallet transactions
        $this->assertDatabaseHas('wallet_transactions', [
            'transaction_type' => 'debit',
            'amount' => 50.00,
            'reference_type' => 'App\Models\PackagePurchase',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'transaction_type' => 'credit',
            'amount' => 30.00,
            'reference_type' => 'App\Models\PackagePurchase',
        ]);
    }

    public function test_sub_session_lifecycle()
    {
        Queue::fake();
        Event::fake();

        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);
        Astrologer::create([
            'user_id' => $provider->id,
            'years_of_experience' => 5,
            'languages' => ['English'],
            'id_proof_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'status' => 'approved',
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);
        
        $purchase = PackagePurchase::create([
            'user_id' => $consumer->id,
            'astrologer_id' => $provider->id,
            'total_duration' => 1800,
            'remaining_duration' => 1800,
            'purchase_price' => 50.00,
            'commission_percentage' => 50.00,
            'astrologer_earnings' => 25.00,
            'admin_earnings' => 25.00,
            'status' => 'active',
        ]);

        // 1. Start Sub-Session (Chat)
        $response = $this->actingAs($consumer)->postJson('/api/v1/packages/session/start', [
            'astrologer_id' => $provider->id,
            'mode' => 'chat',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $subSessionId = $response->json('data.sub_session.id');

        $this->assertDatabaseHas('package_sub_sessions', [
            'id' => $subSessionId,
            'mode' => 'chat',
            'ended_at' => null,
        ]);

        // Verify users marked as busy
        $this->assertTrue(User::find($consumer->id)->is_busy);
        $this->assertTrue(User::find($provider->id)->is_busy);

        // Verify auto-termination job was dispatched
        Queue::assertPushed(TerminatePackageSessionJob::class);

        // 2. Prevent concurrent sub-session
        $response2 = $this->actingAs($consumer)->postJson('/api/v1/packages/session/start', [
            'astrologer_id' => $provider->id,
            'mode' => 'call',
        ]);
        $response2->assertStatus(422);

        // 3. End Sub-Session
        // Let's travel forward in time or simulate it (we'll just travel or manually end it after setting start time back)
        $subSession = PackageSubSession::find($subSessionId);
        $subSession->update(['started_at' => now()->subMinutes(10)]); // Simulate 10 minutes (600 seconds) chat session

        $responseEnd = $this->actingAs($consumer)->postJson('/api/v1/packages/session/end', [
            'sub_session_id' => $subSessionId,
        ]);

        $responseEnd->assertStatus(200);
        $responseEnd->assertJsonPath('success', true);
        
        // Verify remaining duration deducted
        $this->assertDatabaseHas('package_purchases', [
            'id' => $purchase->id,
            'remaining_duration' => 1200, // 1800 - 600 = 1200
            'status' => 'active',
        ]);

        // Verify presence reset to free
        $this->assertFalse(User::find($consumer->id)->is_busy);
        $this->assertFalse(User::find($provider->id)->is_busy);
    }

    public function test_astrologer_list_and_details_contain_package_parameters()
    {
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $provider->id,
            'years_of_experience' => 5,
            'languages' => ['English'],
            'id_proof_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'status' => 'approved',
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);

        // Purchase a package first
        $purchase = PackagePurchase::create([
            'user_id' => $consumer->id,
            'astrologer_id' => $provider->id,
            'total_duration' => 1800,
            'remaining_duration' => 1200, // 20 mins remaining, 10 mins used
            'purchase_price' => 50.00,
            'commission_percentage' => 50.00,
            'astrologer_earnings' => 25.00,
            'admin_earnings' => 25.00,
            'status' => 'active',
        ]);

        // Request list as the authenticated consumer
        $responseList = $this->actingAs($consumer)->getJson('/api/v1/user/astrologers');
        $responseList->assertStatus(200);
        
        $listData = $responseList->json('data.astrologers.0');
        $this->assertNotNull($listData);
        $this->assertArrayHasKey('package_details', $listData);
        $this->assertEquals('Default 30 Mins Pack', $listData['package_details']['name']);
        $this->assertEquals(50.00, $listData['package_details']['price']);
        $this->assertEquals(1800, $listData['package_details']['duration']);
        $this->assertTrue($listData['package_details']['is_purchase']);
        $this->assertEquals(1200, $listData['package_details']['remaining_time']);
        $this->assertEquals(600, $listData['package_details']['used_time']);

        // Request details as the authenticated consumer
        $responseDetails = $this->actingAs($consumer)->getJson('/api/v1/user/astrologers/' . $astrologer->id);
        $responseDetails->assertStatus(200);

        $detailsData = $responseDetails->json('data.astrologer');
        $this->assertNotNull($detailsData);
        $this->assertArrayHasKey('package_details', $detailsData);
        $this->assertEquals('Default 30 Mins Pack', $detailsData['package_details']['name']);
        $this->assertEquals(50.00, $detailsData['package_details']['price']);
        $this->assertEquals(1800, $detailsData['package_details']['duration']);
        $this->assertTrue($detailsData['package_details']['is_purchase']);
        $this->assertEquals(1200, $detailsData['package_details']['remaining_time']);
        $this->assertEquals(600, $detailsData['package_details']['used_time']);
    }
}
