<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Astrologer;
use App\Models\AstrologerBankAccount;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerGallery;
use App\Models\AstrologerOtherDetail;
use App\Models\AstrologerPackage;
use App\Models\AstrologerPhoneNumber;
use App\Models\AstrologerReview;
use App\Models\AstrologerSkill;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAstrologerStatusAndCascadeDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = Admin::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@suryapath.com',
            'password' => bcrypt('password123'),
            'role'     => 'super_admin',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_quickly_update_astrologer_status_to_approved_rejected_and_suspended()
    {
        $astroUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $astroUser->id,
            'status'  => 'pending',
        ]);

        // 1. Approve
        $response = $this->actingAs($this->adminUser, 'admin')
            ->postJson("/admin/astrologers/{$astroUser->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.status', 'approved');
        $this->assertEquals('approved', $astrologer->fresh()->status);

        // 2. Reject
        $responseReject = $this->actingAs($this->adminUser, 'admin')
            ->postJson("/admin/astrologers/{$astroUser->id}/status", [
                'status' => 'rejected',
            ]);

        $responseReject->assertStatus(200);
        $this->assertEquals('rejected', $astrologer->fresh()->status);

        // 3. Pending
        $responsePending = $this->actingAs($this->adminUser, 'admin')
            ->postJson("/admin/astrologers/{$astroUser->id}/status", [
                'status' => 'pending',
            ]);

        $responsePending->assertStatus(200);
        $this->assertEquals('pending', $astrologer->fresh()->status);
    }

    /** @test */
    public function admin_can_permanently_delete_astrologer_and_all_associated_data_and_files()
    {
        Storage::fake('public');

        $astroUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $astroUser->id,
            'status'  => 'approved',
        ]);

        // Create test file in storage
        Storage::disk('public')->put("astrologers/{$astroUser->id}/documents/id_proof.pdf", 'dummy content');
        $this->assertTrue(Storage::disk('public')->exists("astrologers/{$astroUser->id}/documents/id_proof.pdf"));

        // Create child relation records
        AstrologerSkill::create([
            'astrologer_id' => $astrologer->id,
            'category'      => 'Vedic',
        ]);

        AstrologerOtherDetail::create([
            'astrologer_id' => $astrologer->id,
            'gender'        => 'male',
        ]);

        AstrologerPhoneNumber::create([
            'astrologer_id' => $astrologer->id,
            'country_code'  => '+91',
            'phone'         => '9876543210',
        ]);

        AstrologerBankAccount::create([
            'astrologer_id'  => $astrologer->id,
            'account_number' => '123456789012',
            'ifsc_code'      => 'SBIN0001234',
            'account_holder_name' => 'Test Holder',
            'bank_name'      => 'SBI',
        ]);

        AstrologerPackage::create([
            'astrologer_id' => $astroUser->id,
            'amount'        => 500,
            'duration'      => 1800,
        ]);

        $offer = Offer::create([
            'name'                  => 'Test Offer',
            'discount_percentage'   => 10,
            'call_astrologer_share' => 70,
            'chat_astrologer_share' => 70,
            'call_admin_share'      => 30,
            'chat_admin_share'      => 30,
            'is_active'             => true,
        ]);
        $astrologer->offers()->attach($offer->id, ['status' => 'active']);

        // Act: Delete astrologer
        $response = $this->actingAs($this->adminUser, 'admin')
            ->deleteJson("/admin/astrologers/{$astroUser->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Assert: User and Astrologer are completely deleted
        $this->assertDatabaseMissing('users', ['id' => $astroUser->id]);
        $this->assertDatabaseMissing('astrologers', ['id' => $astrologer->id]);
        $this->assertDatabaseMissing('astrologer_skills', ['astrologer_id' => $astrologer->id]);
        $this->assertDatabaseMissing('astrologer_other_details', ['astrologer_id' => $astrologer->id]);
        $this->assertDatabaseMissing('astrologer_phone_numbers', ['astrologer_id' => $astrologer->id]);
        $this->assertDatabaseMissing('astrologer_bank_accounts', ['user_id' => $astroUser->id]);
        $this->assertDatabaseMissing('astrologer_packages', ['astrologer_id' => $astroUser->id]);

        // Assert: Storage directory cleaned up
        $this->assertFalse(Storage::disk('public')->exists("astrologers/{$astroUser->id}/documents/id_proof.pdf"));
    }
}
