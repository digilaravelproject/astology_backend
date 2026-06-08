<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'default_chat_rate_per_minute',
                'value' => '15.00',
                'type' => 'decimal',
                'group' => 'astrologer_pricing',
                'description' => 'Default chat rate per minute for new astrologers (₹)',
            ],
            [
                'key' => 'default_call_rate_per_minute',
                'value' => '15.00',
                'type' => 'decimal',
                'group' => 'astrologer_pricing',
                'description' => 'Default call rate per minute for new astrologers (₹)',
            ],
            [
                'key' => 'default_video_call_rate_per_minute',
                'value' => '15.00',
                'type' => 'decimal',
                'group' => 'astrologer_pricing',
                'description' => 'Default live session (video call) rate per minute for new astrologers (₹)',
            ],
            [
                'key' => 'default_po_at_5_rate_per_minute',
                'value' => '5.00',
                'type' => 'decimal',
                'group' => 'astrologer_pricing',
                'description' => 'Default PO at ₹5 rate per minute for new astrologers (₹)',
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
