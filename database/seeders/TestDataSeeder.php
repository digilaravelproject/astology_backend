<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a Test User (Consumer)
        $user = User::updateOrCreate(
            ['phone' => '9999999999'],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => Hash::make('9999999999'),
                'user_type' => 'user',
                'city' => 'Mumbai',
                'country' => 'India',
            ]
        );

        // Ensure wallet exists
        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            ['balance' => 1000.00]
        );

        // 2. Create a Test Astrologer
        $astroUser = User::updateOrCreate(
            ['phone' => '8888888888'],
            [
                'name' => 'Astro Guru',
                'email' => 'astro@example.com',
                'password' => Hash::make('8888888888'),
                'user_type' => 'astrologer',
                'city' => 'Delhi',
                'country' => 'India',
            ]
        );

        Astrologer::updateOrCreate(
            ['user_id' => $astroUser->id],
            [
                'years_of_experience' => 5,
                'areas_of_expertise' => ['Vedic Astrology', 'Numerology'],
                'languages' => ['Hindi', 'English'],
                'bio' => 'Experienced astrologer for testing purposes.',
                'status' => 'approved',
                'chat_enabled' => true,
                'call_enabled' => true,
                'chat_rate_per_minute' => 10,
                'call_rate_per_minute' => 15,
                'date_of_birth' => '1990-01-01',
                'id_proof_number' => 'ABC12345',
            ]
        );

        $this->command->info('Test Data Seeded Successfully!');
        $this->command->info('User Phone: 9999999999 | OTP: 1234');
        $this->command->info('Astro Phone: 8888888888 | OTP: 1234');
    }
}
