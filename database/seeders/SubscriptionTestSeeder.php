<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create plans if they don't exist
        $plans = [
            [
                'name' => 'Silver Lite',
                'description' => 'Entry level plan',
                'price' => 499,
                'duration_days' => 30,
                'features' => json_encode(['feature1', 'feature2']),
                'status' => 'active',
            ],
            [
                'name' => 'Gold Pro',
                'description' => 'Professional plan',
                'price' => 999,
                'duration_days' => 30,
                'features' => json_encode(['feature1', 'feature2', 'feature3', 'feature4']),
                'status' => 'active',
            ],
            [
                'name' => 'Platinum Elite',
                'description' => 'Premium plan',
                'price' => 2499,
                'duration_days' => 30,
                'features' => json_encode(['feature1', 'feature2', 'feature3', 'feature4', 'feature5', 'feature6']),
                'status' => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }

        // Create test users with active subscriptions
        $plans_data = Plan::all();
        $now = Carbon::now();

        $users_data = [
            [
                'name' => 'Rajesh Khanna',
                'email' => 'rajesh@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Platinum Elite')->first()->id ?? 1,
                'plan_started_at' => $now->copy()->subDays(12),
                'plan_expires_at' => $now->copy()->addDays(18),
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Gold Pro')->first()->id ?? 2,
                'plan_started_at' => $now->copy()->subDays(10),
                'plan_expires_at' => $now->copy()->addDays(20),
            ],
            [
                'name' => 'Amitabh V.',
                'email' => 'amitabh@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Silver Lite')->first()->id ?? 3,
                'plan_started_at' => $now->copy()->subDays(25),
                'plan_expires_at' => $now->copy()->addDays(5),
            ],
            [
                'name' => 'Deepika P.',
                'email' => 'deepika@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Platinum Elite')->first()->id ?? 1,
                'plan_started_at' => $now->copy()->subDays(29),
                'plan_expires_at' => $now->copy()->addDays(1),
            ],
            [
                'name' => 'Ranveer Singh',
                'email' => 'ranveer@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Gold Pro')->first()->id ?? 2,
                'plan_started_at' => $now->copy()->subDays(35),
                'plan_expires_at' => $now->copy()->subDays(5),
            ],
            [
                'name' => 'Salman Khan',
                'email' => 'salman@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Platinum Elite')->first()->id ?? 1,
                'plan_started_at' => $now->copy()->subDays(50),
                'plan_expires_at' => $now->copy()->addDays(10),
            ],
            [
                'name' => 'Shahrukh R.',
                'email' => 'shahrukh@example.com',
                'password' => bcrypt('password'),
                'plan_id' => null,
                'plan_started_at' => null,
                'plan_expires_at' => null,
            ],
            [
                'name' => 'Kareena K.',
                'email' => 'kareena@example.com',
                'password' => bcrypt('password'),
                'plan_id' => $plans_data->where('name', 'Gold Pro')->first()->id ?? 2,
                'plan_started_at' => $now->copy()->subDays(40),
                'plan_expires_at' => $now->copy()->addDays(20),
            ],
        ];

        foreach ($users_data as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }

        $this->command->info('Subscription test data seeded successfully!');
    }
}
