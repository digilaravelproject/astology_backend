<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class WebRTCDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a Consumer (User)
        $consumer = User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Demo Consumer',
                'password' => Hash::make('password'),
                'user_type' => 'user',
                'phone' => '1234567890',
                'city' => 'Mumbai',
                'country' => 'India',
            ]
        );

        // Ensure wallet exists with balance
        Wallet::updateOrCreate(
            ['user_id' => $consumer->id],
            ['balance' => 1000.00]
        );

        // 2. Create a Provider (Astrologer)
        $providerUser = User::updateOrCreate(
            ['email' => 'astro@test.com'],
            [
                'name' => 'Expert Astrologer',
                'password' => Hash::make('password'),
                'user_type' => 'astrologer',
                'phone' => '0987654321',
                'city' => 'Delhi',
                'country' => 'India',
            ]
        );

        Astrologer::updateOrCreate(
            ['user_id' => $providerUser->id],
            [
                'years_of_experience' => 10,
                'areas_of_expertise' => ['Vedic', 'Palmistry'],
                'languages' => ['English', 'Hindi'],
                'status' => 'approved',
                'chat_enabled' => true,
                'call_enabled' => true,
                'video_call_enabled' => true,
                'chat_rate_per_minute' => 10.00,
                'call_rate_per_minute' => 20.00,
                'video_call_rate_per_minute' => 30.00,
                'bio' => 'Professional astrologer with 10 years of experience in Vedic astrology.',
            ]
        );

        $this->command->info('WebRTC Demo Seeder completed successfully!');
        $this->command->info('Consumer: user@test.com / password (Balance: 1000)');
        $this->command->info('Provider: astro@test.com / password (Status: Approved)');
    }
}
