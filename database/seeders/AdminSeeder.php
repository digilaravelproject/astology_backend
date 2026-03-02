<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin if doesn't exist
        Admin::firstOrCreate(
            ['email' => 'admin@astrology.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'), // Change this password after first login
                'phone' => '9876543210',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create additional admin
        Admin::firstOrCreate(
            ['email' => 'moderator@astrology.com'],
            [
                'name' => 'Moderator',
                'password' => Hash::make('moderator123'), // Change this password after first login
                'phone' => '9876543211',
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
