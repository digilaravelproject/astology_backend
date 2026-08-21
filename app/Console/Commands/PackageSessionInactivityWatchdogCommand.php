<?php

namespace App\Console\Commands;

use App\Services\PackageSessionEngineService;
use Illuminate\Console\Command;

class PackageSessionInactivityWatchdogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:inactivity-watchdog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active package sub-sessions and automatically pause abandoned ones to preserve customer minutes';

    /**
     * Execute the console command.
     */
    public function handle(PackageSessionEngineService $engine): int
    {
        $pausedCount = $engine->checkInactivityAndAutoPause();

        if ($pausedCount > 0) {
            $this->info("Auto-paused {$pausedCount} inactive package session(s).");
        }

        return self::SUCCESS;
    }
}
