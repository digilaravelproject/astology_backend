<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astrologerUser;
    protected Astrologer $astrologer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'      => 'Consumer User',
            'phone'     => '9876543210',
            'user_type' => 'user',
            'password'  => bcrypt('password'),
        ]);

        $this->astrologerUser = User::create([
            'name'      => 'Astro User',
            'phone'     => '9876543211',
            'user_type' => 'astrologer',
            'password'  => bcrypt('password'),
        ]);

        $this->astrologer = Astrologer::create([
            'user_id'              => $this->astrologerUser->id,
            'status'               => 'approved',
            'years_of_experience'  => '5',
            'areas_of_expertise'   => ['Vedic'],
            'languages'            => ['Hindi', 'English'],
            'is_online'            => true,
            'is_chat_enabled'      => true,
            'is_call_enabled'      => true,
            'chat_rate_per_minute' => 15.00,
            'call_rate_per_minute' => 15.00,
        ]);
    }

    /** @test */
    public function user_can_get_notification_counts_and_list()
    {
        AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'Notification 1',
            'body'    => 'Body 1',
            'is_read' => false,
        ]);

        AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'Notification 2',
            'body'    => 'Body 2',
            'is_read' => true,
        ]);

        $token = $this->user->createToken('test', ['role:user'])->plainTextToken;

        $resCount = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user/notifications/count');

        $resCount->assertStatus(200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.unread', 1);

        $resList = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user/notifications');

        $resList->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read()
    {
        AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'Notification 1',
            'body'    => 'Body 1',
            'is_read' => false,
        ]);

        AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'Notification 2',
            'body'    => 'Body 2',
            'is_read' => false,
        ]);

        $token = $this->user->createToken('test', ['role:user'])->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/user/notifications/mark-all-read');

        $res->assertStatus(200)
            ->assertJsonPath('data.updated_count', 2);

        $this->assertEquals(0, AppNotification::where('user_id', $this->user->id)->where('is_read', false)->count());
    }

    /** @test */
    public function user_can_delete_single_and_all_notifications()
    {
        $n1 = AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'User Notif 1',
            'body'    => 'Body',
            'is_read' => false,
        ]);

        $n2 = AppNotification::create([
            'user_id' => $this->user->id,
            'title'   => 'User Notif 2',
            'body'    => 'Body',
            'is_read' => false,
        ]);

        $userToken = $this->user->createToken('test', ['role:user'])->plainTextToken;

        // 1. Delete single notification for user
        $delSingle = $this->withHeader('Authorization', "Bearer {$userToken}")
            ->deleteJson("/api/v1/user/notifications/{$n1->id}");
        $delSingle->assertStatus(200);
        $this->assertDatabaseMissing('app_notifications', ['id' => $n1->id]);

        // 2. Delete all notifications for user
        $delAll = $this->withHeader('Authorization', "Bearer {$userToken}")
            ->deleteJson('/api/v1/user/notifications');
        $delAll->assertStatus(200)
            ->assertJsonPath('data.deleted_count', 1);

        $this->assertDatabaseMissing('app_notifications', ['id' => $n2->id]);
    }

    /** @test */
    public function astrologer_can_delete_all_notifications()
    {
        $astroNotif = AppNotification::create([
            'user_id' => $this->astrologerUser->id,
            'title'   => 'Astro Notif 1',
            'body'    => 'Body',
            'is_read' => false,
        ]);

        $astroToken = $this->astrologerUser->createToken('test', ['role:astrologer'])->plainTextToken;

        $delAstroAll = $this->withHeader('Authorization', "Bearer {$astroToken}")
            ->postJson('/api/v1/astrologer/notifications/delete-all');

        $delAstroAll->assertStatus(200)
            ->assertJsonPath('data.deleted_count', 1);

        $this->assertDatabaseMissing('app_notifications', ['id' => $astroNotif->id]);
    }
}
