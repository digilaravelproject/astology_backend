<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Astrologer;
use App\Models\Setting;

class BackfillAstrologerPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Only updates astrologers where pricing is 0 or NULL.
     */
    public function run(): void
    {
        $chatRate = Setting::get('default_chat_rate_per_minute', 15.00);
        $callRate = Setting::get('default_call_rate_per_minute', 15.00);
        $videoCallRate = Setting::get('default_video_call_rate_per_minute', 15.00);
        $poAt5Rate = Setting::get('default_po_at_5_rate_per_minute', 5.00);

        $updated = Astrologer::where(function ($query) {
            $query->where('chat_rate_per_minute', 0)
                ->orWhereNull('chat_rate_per_minute')
                ->orWhere('call_rate_per_minute', 0)
                ->orWhereNull('call_rate_per_minute')
                ->orWhere('video_call_rate_per_minute', 0)
                ->orWhereNull('video_call_rate_per_minute')
                ->orWhere('po_at_5_rate_per_minute', 0)
                ->orWhereNull('po_at_5_rate_per_minute');
        })->update([
            'chat_rate_per_minute' => $chatRate,
            'call_rate_per_minute' => $callRate,
            'video_call_rate_per_minute' => $videoCallRate,
            'po_at_5_rate_per_minute' => $poAt5Rate,
        ]);

        $this->command->info("Backfilled pricing for {$updated} astrologer(s).");
    }
}
