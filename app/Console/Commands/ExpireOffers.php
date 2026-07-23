<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpireOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire astrologer offer activations when the offer expiry date is reached.';

    /**
     * Execute the console command.
     */
     public function handle()
     {
         $now = now();
 
         $updated = \Illuminate\Support\Facades\DB::table('astrologer_offers')
             ->join('offers', 'astrologer_offers.offer_id', '=', 'offers.id')
             ->where('astrologer_offers.status', 'active')
             ->whereNotNull('offers.expires_at')
             ->where('offers.expires_at', '<', $now)
             ->update([
                 'astrologer_offers.status' => 'complete',
                 'astrologer_offers.deactivated_at' => $now,
                 'astrologer_offers.updated_at' => $now
             ]);
 
         $this->info("Successfully expired {$updated} active astrologer offer(s).");
     }
}
