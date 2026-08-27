<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function store_device_token_deletes_previous_devices_for_same_user()
    {
        $user = User::factory()->create(['user_type' => 'user']);
        Sanctum::actingAs($user, ['*']);

        // First device
        UserDevice::create([
            'user_id'      => $user->id,
            'fcm_token'    => 'token_old_samsung',
            'device_model' => 'Samsung A54',
            'is_active'    => true,
        ]);

        $this->assertEquals(1, UserDevice::where('user_id', $user->id)->count());

        // Store new device token (e.g. Xiaomi)
        $res = $this->postJson('/api/v1/user/device-token', [
            'fcm_token'    => 'token_new_xiaomi',
            'device_type'  => 'android',
            'device_model' => 'Xiaomi M20',
        ]);

        $res->assertStatus(200);

        // Old Samsung record must be deleted completely
        $this->assertDatabaseMissing('user_devices', ['fcm_token' => 'token_old_samsung']);
        $this->assertDatabaseHas('user_devices', ['fcm_token' => 'token_new_xiaomi']);
        $this->assertEquals(1, UserDevice::where('user_id', $user->id)->count());
        $this->assertEquals('token_new_xiaomi', $user->fresh()->fcm_token);
    }

    /** @test */
    public function remove_device_token_permanently_deletes_record_and_clears_user_token()
    {
        $user = User::factory()->create([
            'user_type' => 'user',
            'fcm_token' => 'token_to_remove',
        ]);
        Sanctum::actingAs($user, ['*']);

        UserDevice::create([
            'user_id'      => $user->id,
            'fcm_token'    => 'token_to_remove',
            'device_model' => 'Samsung A54',
            'is_active'    => true,
        ]);

        $res = $this->deleteJson('/api/v1/user/device-token', [
            'fcm_token' => 'token_to_remove',
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseMissing('user_devices', ['fcm_token' => 'token_to_remove']);
        $this->assertNull($user->fresh()->fcm_token);
    }

    /** @test */
    public function logout_permanently_deletes_device_records()
    {
        $user = User::factory()->create([
            'user_type' => 'user',
            'fcm_token' => 'token_active',
        ]);
        Sanctum::actingAs($user, ['*']);

        UserDevice::create([
            'user_id'      => $user->id,
            'fcm_token'    => 'token_active',
            'device_model' => 'Xiaomi M20',
            'is_active'    => true,
        ]);

        $res = $this->postJson('/api/v1/user/logout', [
            'fcm_token' => 'token_active',
        ]);

        $res->assertStatus(200);
        $this->assertEquals(0, UserDevice::where('user_id', $user->id)->count());
        $this->assertNull($user->fresh()->fcm_token);
    }
}
