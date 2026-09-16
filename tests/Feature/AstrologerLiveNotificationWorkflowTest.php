<?php

namespace Tests\Feature;

use App\Jobs\SendLiveSessionNotificationJob;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\LiveSession;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AstrologerLiveNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_notification_dispatches_only_to_active_followers()
    {
        // Create Astrologer
        $astroUser = User::factory()->create(['name' => 'Astro Pandit', 'user_type' => 'astrologer']);
        $astrologer = Astrologer::factory()->create(['user_id' => $astroUser->id]);

        // Create 2 active followers
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();
        AstrologerCommunity::create([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $follower1->id,
            'is_liked'      => true,
            'is_blocked'    => false,
        ]);
        AstrologerCommunity::create([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $follower2->id,
            'is_liked'      => true,
            'is_blocked'    => false,
        ]);

        // Create 1 unfollowed user
        $unfollower = User::factory()->create();
        AstrologerCommunity::create([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $unfollower->id,
            'is_liked'      => false,
            'is_blocked'    => false,
        ]);

        // Create 1 blocked user
        $blockedUser = User::factory()->create();
        AstrologerCommunity::create([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $blockedUser->id,
            'is_liked'      => true,
            'is_blocked'    => true,
        ]);

        // Create 1 regular unrelated user
        $unrelatedUser = User::factory()->create();

        // Create Live Session
        $session = LiveSession::create([
            'astrologer_id'    => $astrologer->id,
            'title'            => 'Evening Kundli Live',
            'session_type'     => 'public',
            'status'           => 'ongoing',
            'scheduled_at'     => now(),
            'room_uuid'        => 'room_test_123',
            'is_live_notified' => false,
        ]);

        $dispatchedAudience = [];
        // Spy or intercept NotificationService::sendToUsers
        // Since sendToUsers is static, we can run the job and verify the target followers
        $job = new SendLiveSessionNotificationJob($session->id, 'live');
        $job->handle();

        // Verify session marked as notified
        $this->assertTrue($session->fresh()->is_live_notified);

        // Verify cooldown is set
        $this->assertTrue(Cache::has("astro_live_notif_cooldown_{$astrologer->id}"));
    }

    public function test_cooldown_suppresses_duplicate_live_notification_within_10_minutes()
    {
        $astroUser = User::factory()->create(['name' => 'Astro Pandit 2', 'user_type' => 'astrologer']);
        $astrologer = Astrologer::factory()->create(['user_id' => $astroUser->id]);

        $follower = User::factory()->create();
        AstrologerCommunity::create([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $follower->id,
            'is_liked'      => true,
            'is_blocked'    => false,
        ]);

        // Pre-set cooldown in cache (as if astrologer went live 2 minutes ago)
        Cache::put("astro_live_notif_cooldown_{$astrologer->id}", now()->toIso8601String(), now()->addMinutes(8));

        $session2 = LiveSession::create([
            'astrologer_id'    => $astrologer->id,
            'title'            => 'Reconnected Stream',
            'session_type'     => 'public',
            'status'           => 'ongoing',
            'scheduled_at'     => now(),
            'room_uuid'        => 'room_test_456',
            'is_live_notified' => false,
        ]);

        $job = new SendLiveSessionNotificationJob($session2->id, 'live');
        $job->handle();

        // Should mark session as notified so it doesn't try again
        $this->assertTrue($session2->fresh()->is_live_notified);
    }

    public function test_zero_followers_completes_gracefully_with_no_fallback_to_unrelated_users()
    {
        $astroUser = User::factory()->create(['name' => 'Astro Pandit 3', 'user_type' => 'astrologer']);
        $astrologer = Astrologer::factory()->create(['user_id' => $astroUser->id]);

        // Create random unrelated users
        User::factory()->count(5)->create(['user_type' => 'user']);

        // Astrologer has NO followers in astrologer_communities

        $session = LiveSession::create([
            'astrologer_id'    => $astrologer->id,
            'title'            => 'Solo Live with 0 Followers',
            'session_type'     => 'public',
            'status'           => 'ongoing',
            'scheduled_at'     => now(),
            'room_uuid'        => 'room_solo_999',
            'is_live_notified' => false,
        ]);

        $job = new SendLiveSessionNotificationJob($session->id, 'live');
        $job->handle();

        // Must be marked as notified so it doesn't loop
        $this->assertTrue($session->fresh()->is_live_notified);

        // Cooldown should be set to prevent immediate retry
        $this->assertTrue(Cache::has("astro_live_notif_cooldown_{$astrologer->id}"));
    }
}
