<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\Wallet;
use App\Services\ChatService;
use App\Services\CallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class BidirectionalBlockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_block_and_unblock_astrologer()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer', 'name' => 'Guruji']);
        $astrologer = Astrologer::create([
            'user_id' => $astrologerUser->id,
            'years_of_experience' => 5,
            'chat_rate_per_minute' => 20.00,
            'call_rate_per_minute' => 25.00,
        ]);

        // 1. Block astrologer
        $response = $this->actingAs($user)->postJson("/api/v1/user/astrologers/{$astrologer->id}/block", [
            'reason' => 'Misbehavior in session'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_blocked', true);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $user->id,
            'blocked_id' => $astrologerUser->id,
        ]);

        // 2. Fetch blocked list
        $listResponse = $this->actingAs($user)->getJson('/api/v1/user/blocked-astrologers');
        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $astrologer->id);

        // 3. Unblock astrologer
        $unblockResponse = $this->actingAs($user)->postJson("/api/v1/user/astrologers/{$astrologer->id}/unblock");
        $unblockResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_blocked', false);

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $user->id,
            'blocked_id' => $astrologerUser->id,
        ]);

        // 4. Blocked list is now empty
        $emptyList = $this->actingAs($user)->getJson('/api/v1/user/blocked-astrologers');
        $emptyList->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }

    /** @test */
    public function astrologer_can_block_and_unblock_user()
    {
        /** @var \App\Models\User $astrologerUser */
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $astrologerUser->id,
            'years_of_experience' => 5,
        ]);

        $targetUser = User::factory()->create(['user_type' => 'user', 'name' => 'Spam User']);

        // 1. Astrologer blocks user
        $blockResponse = $this->actingAs($astrologerUser)->postJson("/api/v1/astrologer/users/{$targetUser->id}/block", [
            'reason' => 'Abusive language'
        ]);

        $blockResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_blocked', true);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $astrologerUser->id,
            'blocked_id' => $targetUser->id,
        ]);

        // 2. Fetch blocked users list
        $listResponse = $this->actingAs($astrologerUser)->getJson('/api/v1/astrologer/blocked-users');
        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $targetUser->id);

        // 3. Unblock user
        $unblockResponse = $this->actingAs($astrologerUser)->postJson("/api/v1/astrologer/users/{$targetUser->id}/unblock");
        $unblockResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_blocked', false);

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $astrologerUser->id,
            'blocked_id' => $targetUser->id,
        ]);
    }

    /** @test */
    public function astrologers_listing_and_details_correctly_reflect_and_filter_blocked_status()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer', 'name' => 'Vedic Pandit']);
        $astrologer = Astrologer::create([
            'user_id' => $astrologerUser->id,
            'years_of_experience' => 10,
            'chat_rate_per_minute' => 15.00,
            'call_rate_per_minute' => 15.00,
        ]);

        // Details before block: is_blocked = false
        $detailsBefore = $this->actingAs($user)->getJson("/api/v1/user/astrologers/{$astrologer->id}");
        $detailsBefore->assertStatus(200)
            ->assertJsonPath('data.astrologer.is_blocked', false);

        // Listing before block: astrologer is visible
        $listBefore = $this->actingAs($user)->getJson('/api/v1/user/astrologers');
        $listBefore->assertStatus(200)
            ->assertJsonCount(1, 'data.astrologers')
            ->assertJsonPath('data.astrologers.0.is_blocked', false);

        // User blocks astrologer
        $this->actingAs($user)->postJson("/api/v1/user/astrologers/{$astrologer->id}/block");

        // Details after block: is_blocked = true
        $detailsAfter = $this->actingAs($user)->getJson("/api/v1/user/astrologers/{$astrologer->id}");
        $detailsAfter->assertStatus(200)
            ->assertJsonPath('data.astrologer.is_blocked', true);

        // Listing after block: blocked astrologer is hidden by default from discovery
        $listAfter = $this->actingAs($user)->getJson('/api/v1/user/astrologers');
        $listAfter->assertStatus(200)
            ->assertJsonCount(0, 'data.astrologers');

        // Listing with include_blocked=1 shows astrologer with is_blocked: true
        $listIncluded = $this->actingAs($user)->getJson('/api/v1/user/astrologers?include_blocked=1');
        $listIncluded->assertStatus(200)
            ->assertJsonCount(1, 'data.astrologers')
            ->assertJsonPath('data.astrologers.0.is_blocked', true);
    }

    /** @test */
    public function chat_and_call_cannot_be_initiated_if_blocked_by_either_party()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $astrologerUser->id,
            'years_of_experience' => 5,
            'chat_rate_per_minute' => 10.00,
            'call_rate_per_minute' => 10.00,
            'is_chat_enabled' => true,
            'is_call_enabled' => true,
        ]);

        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        /** @var ChatService $chatService */
        $chatService = app(ChatService::class);
        /** @var CallService $callService */
        $callService = app(CallService::class);

        // User blocks astrologer
        $this->actingAs($user)->postJson("/api/v1/user/astrologers/{$astrologer->id}/block");

        // 1. Chat initiation should fail with block exception
        $chatBlocked = false;
        try {
            $chatService->initiateChat($user->id, $astrologerUser->id);
        } catch (Exception $e) {
            $chatBlocked = str_contains($e->getMessage(), 'block');
        }
        $this->assertTrue($chatBlocked, "Expected chat to be blocked.");

        // 2. Call initiation should fail with block exception
        $callBlocked = false;
        try {
            $callService->initiateCall($user->id, $astrologerUser->id);
        } catch (Exception $e) {
            $callBlocked = str_contains($e->getMessage(), 'block');
        }
        $this->assertTrue($callBlocked, "Expected call to be blocked.");

        // Unblock
        $this->actingAs($user)->postJson("/api/v1/user/astrologers/{$astrologer->id}/unblock");

        // 3. Astrologer blocks user
        $this->actingAs($astrologerUser)->postJson("/api/v1/astrologer/users/{$user->id}/block");

        // Chat initiation should fail when astrologer has blocked user
        $chatBlockedByAstro = false;
        try {
            $chatService->initiateChat($user->id, $astrologerUser->id);
        } catch (Exception $e) {
            $chatBlockedByAstro = str_contains($e->getMessage(), 'block');
        }
        $this->assertTrue($chatBlockedByAstro, "Expected chat to be blocked when astrologer blocked user.");
    }
}
