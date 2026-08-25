<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Kundli;
use App\Models\User;
use App\Services\NormalChatService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KundliTimezoneAndDataAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astrologer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'user_type' => 'user',
            'date_of_birth' => null,
            'time_of_birth' => null,
            'place_of_birth' => null,
        ]);

        $this->astrologer = User::factory()->create([
            'user_type' => 'astrologer',
        ]);
    }

    /** @test */
    public function user_can_create_kundli_with_12_hour_am_pm_time_and_it_normalizes_to_asia_kolkata()
    {
        $payload = [
            'name' => 'Aditi Sharma',
            'gender' => 'female',
            'birth_date' => '1998-11-20',
            'birth_time' => '02:30 PM', // 12-hour format
            'birth_place' => 'Varanasi, UP',
            'latitude' => 25.3176,
            'longitude' => 82.9739,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/kundli/create', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.birth_time', '14:30:00');
        $response->assertJsonPath('data.datetime', '1998-11-20 14:30:00');

        $this->assertDatabaseHas('kundlis', [
            'user_id' => $this->user->id,
            'name' => 'Aditi Sharma',
            'birth_time' => '14:30:00',
            'datetime' => '1998-11-20 14:30:00',
        ]);
    }

    /** @test */
    public function user_can_create_kundli_with_24_hour_time_without_seconds()
    {
        $payload = [
            'name' => 'Rohan Verma',
            'gender' => 'male',
            'birth_date' => '1995-05-15',
            'birth_time' => '09:45',
            'birth_place' => 'New Delhi',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/kundli', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.birth_time', '09:45:00');
        $response->assertJsonPath('data.datetime', '1995-05-15 09:45:00');
    }

    /** @test */
    public function updating_kundli_resynchronizes_datetime_accurately()
    {
        $kundli = Kundli::create([
            'user_id' => $this->user->id,
            'name' => 'Vikram',
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'birth_time' => '10:00:00',
            'birth_place' => 'Jaipur',
            'latitude' => 26.9124,
            'longitude' => 75.7873,
            'datetime' => '2000-01-01 10:00:00',
        ]);

        $updatePayload = [
            'birth_time' => '11:15 PM', // changing to 23:15:00
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/kundli/{$kundli->id}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.birth_time', '23:15:00');
        $response->assertJsonPath('data.datetime', '2000-01-01 23:15:00');
    }

    /** @test */
    public function normal_chat_service_falls_back_to_saved_kundli_when_user_profile_is_empty()
    {
        // User profile has empty date_of_birth and time_of_birth
        $this->assertNull($this->user->date_of_birth);

        // User saves a Kundli
        $kundli = Kundli::create([
            'user_id' => $this->user->id,
            'name' => 'Pooja',
            'gender' => 'female',
            'birth_date' => '1996-08-15',
            'birth_time' => '07:30:00',
            'birth_place' => 'Haridwar',
            'latitude' => 29.9457,
            'longitude' => 78.1642,
            'datetime' => '1996-08-15 07:30:00',
        ]);

        $chatSession = ChatSession::create([
            'consumer_id' => $this->user->id,
            'provider_id' => $this->astrologer->id,
            'status' => 'initiated',
            'question' => 'Career guidance',
        ]);

        $chatService = app(NormalChatService::class);
        $reflection = new \ReflectionClass($chatService);
        $method = $reflection->getMethod('formatUserDetailsMessage');
        $method->setAccessible(true);

        $messageText = $method->invoke($chatService, $this->user, $chatSession);

        $this->assertStringContainsString('• Name: ' . ($this->user->name ?? 'Pooja'), $messageText);
        $this->assertStringContainsString('• DOB: 15 Aug 1996', $messageText);
        $this->assertStringContainsString('• TOB: 07:30 AM', $messageText);
        $this->assertStringContainsString('• POB: Haridwar', $messageText);
    }
}
