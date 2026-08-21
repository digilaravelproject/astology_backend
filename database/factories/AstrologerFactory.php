<?php

namespace Database\Factories;

use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Astrologer>
 */
class AstrologerFactory extends Factory
{
    protected $model = Astrologer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'years_of_experience' => '5',
            'areas_of_expertise' => ['Vedic', 'Tarot', 'Numerology'],
            'languages' => ['English', 'Hindi'],
            'bio' => fake()->paragraph(),
            'status' => 'approved',
            'is_online' => true,
            'is_chat_enabled' => true,
            'is_call_enabled' => true,
            'is_video_call_enabled' => true,
            'chat_rate_per_minute' => 15.00,
            'call_rate_per_minute' => 15.00,
            'video_call_rate_per_minute' => 20.00,
            'po_at_5_enabled' => false,
            'po_at_5_rate_per_minute' => 5.00,
            'po_at_5_sessions' => 0,
        ];
    }
}
