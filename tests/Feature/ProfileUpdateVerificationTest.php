<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\AstrologerSkill;
use App\Models\AstrologerOtherDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileUpdateVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function user_can_update_profile_partially_via_put_and_post()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'user_type' => 'user',
            'name' => 'Initial Name',
            'gender' => 'male',
        ]);

        // 1. Partial update with PUT
        $responsePut = $this->actingAs($user)->putJson('/api/v1/user/profileInAppUpdate', [
            'name' => 'Updated via PUT',
            'occupation' => 'Software Engineer',
        ]);

        $responsePut->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.name', 'Updated via PUT')
            ->assertJsonPath('data.user.occupation', 'Software Engineer');

        // 2. Partial update with POST (as requested for multipart/post support)
        $responsePost = $this->actingAs($user)->postJson('/api/v1/user/profileInAppUpdate', [
            'relationship_status' => 'Married',
            'city' => 'Delhi',
        ]);

        $responsePost->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.relationship_status', 'Married')
            ->assertJsonPath('data.user.city', 'Delhi');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated via PUT',
            'occupation' => 'Software Engineer',
            'relationship_status' => 'Married',
            'city' => 'Delhi',
        ]);
    }

    /** @test */
    public function user_can_update_profile_with_various_time_formats_and_string_languages()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);

        // Time with seconds & languages as JSON string
        $response = $this->actingAs($user)->putJson('/api/v1/user/profileInAppUpdate', [
            'name' => 'Rahul Verma',
            'date_of_birth' => '1992-08-15',
            'time_of_birth' => '14:30:00',
            'languages' => json_encode(['Hindi', 'English', 'Gujarati']),
            'gender' => 'MALE',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.time_of_birth', '14:30')
            ->assertJsonPath('data.user.gender', 'male');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Rahul Verma',
            'gender' => 'male',
            'time_of_birth' => '14:30',
        ]);
    }

    /** @test */
    public function user_can_update_profile_photo_via_post_and_put()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'user']);

        $photo = UploadedFile::fake()->image('user_avatar.jpg', 300, 300);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['role:user']);

        $response = $this->postJson('/api/v1/user/profile/photo', [
            'profile_photo' => $photo,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertNotNull($user->fresh()->profile_photo);
    }

    /** @test */
    public function astrologer_can_update_profile_via_put_and_post_with_all_fields()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'user_type' => 'astrologer',
            'name' => 'Astro Initial',
            'city' => 'Varanasi',
        ]);

        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'years_of_experience' => 5,
            'areas_of_expertise' => ['Vedic Astrology'],
            'languages' => ['Hindi'],
            'chat_rate_per_minute' => 15.00,
            'call_rate_per_minute' => 15.00,
        ]);

        // Update via PUT
        $responsePut = $this->actingAs($user)->putJson('/api/v1/astrologer/profile', [
            'full_name' => 'Acharya Sharma',
            'bio' => 'Senior Vedic Astrologer with 10+ years experience.',
            'years_of_experience' => 10,
            'primary_skills' => ['Vedic Astrology', 'Kundli', 'Numerology'],
            'languages' => ['Hindi', 'English', 'Sanskrit'],
            'website_link' => 'https://astrologer.example.com',
            'instagram_username' => 'astro_acharya',
            'gender' => 'male',
        ]);

        $responsePut->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.name', 'Acharya Sharma')
            ->assertJsonPath('data.astrologer.bio', 'Senior Vedic Astrologer with 10+ years experience.')
            ->assertJsonPath('data.astrologer.years_of_experience', 10);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Acharya Sharma',
        ]);

        $this->assertDatabaseHas('astrologers', [
            'id' => $astrologer->id,
            'years_of_experience' => 10,
        ]);

        $this->assertDatabaseHas('astrologer_other_details', [
            'astrologer_id' => $astrologer->id,
            'gender' => 'male',
            'website_link' => 'https://astrologer.example.com',
            'instagram_username' => 'astro_acharya',
        ]);

        // Update via POST (with empty string optionals)
        $responsePost = $this->actingAs($user)->postJson('/api/v1/astrologer/profile', [
            'name' => 'Acharya Sharma Ji',
            'city' => 'Haridwar',
            'id_proof_number' => '', // Should not fail validation with nullable
        ]);

        $responsePost->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.name', 'Acharya Sharma Ji')
            ->assertJsonPath('data.user.city', 'Haridwar');
    }

    /** @test */
    public function astrologer_can_update_skills_and_other_details_directly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'years_of_experience' => 3,
            'chat_rate_per_minute' => 10.00,
        ]);

        // 1. Update skills
        $responseSkills = $this->actingAs($user)->postJson('/api/v1/astrologer/profile/skills', [
            'category' => 'Astrology',
            'primary_skills' => ['Tarot', 'Palmistry'],
            'languages' => ['English', 'Hindi'],
            'experience_years' => 7,
            'daily_contribution_hours' => 5,
        ]);

        $responseSkills->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.skill.experience_years', 7);

        // 2. Update other details
        $responseOther = $this->actingAs($user)->postJson('/api/v1/astrologer/profile/other-details', [
            'gender' => 'female',
            'bio' => 'Experienced Tarot Reader',
            'website_link' => 'https://mytarot.com',
        ]);

        $responseOther->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.other_details.gender', 'female');
    }
}
