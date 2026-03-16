<?php

namespace Database\Seeders;

use App\Models\Notice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Notice::truncate();

        Notice::create([
            'title' => 'System Maintenance',
            'body' => 'The system will be under maintenance on 22nd Feb from 2:00 AM to 4:00 AM. Services might be disrupted during this period.',
            'tag' => 'Technical',
            'is_urgent' => true,
            'icon' => 'settings',
            'is_active' => true,
        ]);

        Notice::create([
            'title' => 'New Payout Cycle',
            'body' => 'Good news! We have updated our payout cycle. Now you will receive your earnings every Monday instead of every 15 days.',
            'tag' => 'Account',
            'is_urgent' => false,
            'icon' => 'wallet',
            'is_active' => true,
        ]);

        Notice::create([
            'title' => 'Policy Update',
            'body' => 'Please review our updated terms and conditions regarding consultation rates. Effective from 1st March.',
            'tag' => 'Policy',
            'is_urgent' => false,
            'icon' => 'document',
            'is_active' => true,
        ]);
    }
}
