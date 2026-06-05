<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\CallSession;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_render_dashboard_without_errors()
    {
        // Create an admin
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create some consumers and providers
        $consumer = User::factory()->create(['user_type' => 'user']);
        $provider = User::factory()->create(['user_type' => 'astrologer']);

        // Create completed call session
        CallSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'rate_per_minute' => 10,
            'duration_seconds' => 60,
            'total_cost' => 10,
            'completed_at' => now(),
        ]);

        // Create completed chat session
        ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
            'rate_per_minute' => 10,
            'duration_seconds' => 60,
            'total_cost' => 10,
            'completed_at' => now(),
        ]);

        // Make the request acting as the admin
        $this->withoutExceptionHandling();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('recentOrders');
    }
}
