<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LiveSessionService;

class TerminateAllLiveSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:terminate-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forcefully terminate all currently ongoing live sessions and broadcast updates';

    /**
     * Execute the console command.
     */
    public function handle(LiveSessionService $service): int
    {
        $this->info('Terminating all ongoing live sessions...');
        $count = $service->terminateAllOngoingSessions();
        $this->info("Successfully terminated {$count} ongoing live session(s).");

        return 0;
    }
}
