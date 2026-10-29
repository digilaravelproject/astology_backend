<?php

namespace App\Console\Commands;

use App\Services\CallService;
use Illuminate\Console\Command;

class CallSessionInactivityWatchdogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:inactivity-watchdog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and automatically clean up abandoned or hanging call sessions';

    /**
     * Execute the console command.
     */
    public function handle(CallService $service): int
    {
        $cleaned = $service->cleanupAbandonedSessions();

        if ($cleaned > 0) {
            $this->info("Cleaned up {$cleaned} abandoned call session(s).");
        }

        return self::SUCCESS;
    }
}

