<?php

namespace Tests\Feature;

use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\Kundli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KundliDeleteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected User $astroUser;
    protected Astrologer $astrologer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::create([
            'name' => 'User One',
            'phone' => '9876543210',
            'country_code' => '+91',
            'password' => bcrypt('password'),
            'user_type' => 'user',
        ]);

        $this->user2 = User::create([
            'name' => 'User Two',
            'phone' => '9876543211',
            'country_code' => '+91',
            'password' => bcrypt('password'),
            'user_type' => 'user',
        ]);

        $this->astroUser = User::create([
            'name' => 'Astro Guru',
            'phone' => '9876543212',
            'country_code' => '+91',
            'password' => bcrypt('password'),
            'user_type' => 'astrologer',
        ]);

        $this->astrologer = Astrologer::create([
            'user_id' => $this->astroUser->id,
            'status' => 'approved',
            'is_online' => true,
        ]);
    }

    public function test_user_can_delete_their_own_kundli(): void
    {
        $kundli = Kundli::create([
            'user_id' => $this->user1->id,
            'name' => 'John Doe Kundli',
            'gender' => 'male',
            'birth_date' => '1995-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'New Delhi',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'datetime' => '1995-05-15 10:30:00',
        ]);

        Sanctum::actingAs($this->user1, ['*']);

        $response = $this->deleteJson("/api/v1/user/kundli/{$kundli->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Kundli deleted successfully');

        $this->assertDatabaseMissing('kundlis', [
            'id' => $kundli->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_kundli(): void
    {
        $kundli = Kundli::create([
            'user_id' => $this->user2->id,
            'name' => 'User Two Kundli',
            'gender' => 'female',
            'birth_date' => '1998-08-20',
            'birth_time' => '14:15:00',
            'birth_place' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'datetime' => '1998-08-20 14:15:00',
        ]);

        // Acting as user 1
        Sanctum::actingAs($this->user1, ['*']);

        $response = $this->deleteJson("/api/v1/user/kundli/{$kundli->id}");

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Kundli not found');

        $this->assertDatabaseHas('kundlis', [
            'id' => $kundli->id,
        ]);
    }

    public function test_astrologer_can_delete_consulted_clients_kundli(): void
    {
        $kundli = Kundli::create([
            'user_id' => $this->user1->id,
            'name' => 'Client Kundli for Astrologer',
            'gender' => 'male',
            'birth_date' => '1992-01-01',
            'birth_time' => '08:00:00',
            'birth_place' => 'Varanasi',
            'latitude' => 25.3176,
            'longitude' => 82.9739,
            'datetime' => '1992-01-01 08:00:00',
        ]);

        // Create a consultation record between astrologer and user 1
        ChatSession::create([
            'consumer_id' => $this->user1->id,
            'provider_id' => $this->astroUser->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'duration_seconds' => 300,
            'rate_per_minute' => 20.0,
            'total_cost' => 100.0,
        ]);

        Sanctum::actingAs($this->astroUser, ['*']);

        $response = $this->deleteJson("/api/v1/astrologer/kundli/{$kundli->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Kundli deleted successfully');

        $this->assertDatabaseMissing('kundlis', [
            'id' => $kundli->id,
        ]);
    }

    public function test_astrologer_cannot_delete_stranger_users_kundli(): void
    {
        // Kundli of user 2 (who never consulted with this astrologer)
        $kundli = Kundli::create([
            'user_id' => $this->user2->id,
            'name' => 'Stranger User Kundli',
            'gender' => 'female',
            'birth_date' => '1994-04-12',
            'birth_time' => '09:00:00',
            'birth_place' => 'Jaipur',
            'latitude' => 26.9124,
            'longitude' => 75.7873,
            'datetime' => '1994-04-12 09:00:00',
        ]);

        Sanctum::actingAs($this->astroUser, ['*']);

        $response = $this->deleteJson("/api/v1/astrologer/kundli/{$kundli->id}");

        $response->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Unauthorized. You can only delete Kundlis of clients you have consulted with.');

        $this->assertDatabaseHas('kundlis', [
            'id' => $kundli->id,
        ]);
    }

    public function test_astrologer_deleting_non_existent_kundli_returns_404(): void
    {
        Sanctum::actingAs($this->astroUser, ['*']);

        $response = $this->deleteJson("/api/v1/astrologer/kundli/999999");

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Kundli not found');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $responseUser = $this->deleteJson("/api/v1/user/kundli/1");
        $responseUser->assertStatus(401);

        $responseAstro = $this->deleteJson("/api/v1/astrologer/kundli/1");
        $responseAstro->assertStatus(401);
    }
}
