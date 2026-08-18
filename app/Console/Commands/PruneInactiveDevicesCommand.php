<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneInactiveDevicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:prune-devices {--days=90 : Prune inactive devices older than X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old inactive FCM user devices from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Pruning inactive FCM devices not active since {$cutoff->toDateTimeString()}...");

        $deletedCount = UserDevice::where('is_active', false)
            ->where(function ($query) use ($cutoff) {
                $query->where('updated_at', '<', $cutoff)
                      ->orWhere(function ($q) use ($cutoff) {
                          $q->whereNull('updated_at')
                            ->where('created_at', '<', $cutoff);
                      });
            })
            ->delete();

        $this->info("Successfully pruned {$deletedCount} inactive device records.");

        return Command::SUCCESS;
    }
}
