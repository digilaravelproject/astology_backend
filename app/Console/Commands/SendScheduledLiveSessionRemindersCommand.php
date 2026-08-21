<?php

namespace App\Console\Commands;

use App\Jobs\SendLiveSessionNotificationJob;
use App\Models\LiveSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledLiveSessionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:send-scheduled-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan upcoming live sessions and dispatch smart reminder push notifications 5-10 minutes prior';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $windowEnd = $now->copy()->addMinutes(10);

        // Find upcoming live sessions scheduled within the next 10 minutes that have not sent reminder notifications
        $upcomingSessions = LiveSession::where('status', 'upcoming')
            ->where('is_reminder_notified', false)
            ->whereBetween('scheduled_at', [$now, $windowEnd])
            ->get();

        $count = $upcomingSessions->count();
        if ($count > 0) {
            $this->info("Found {$count} upcoming live session(s) due for reminders.");
            foreach ($upcomingSessions as $session) {
                try {
                    SendLiveSessionNotificationJob::dispatch($session->id, 'reminder');
                    $this->line("Dispatched reminder job for LiveSession #{$session->id} (Scheduled at: {$session->scheduled_at})");
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch reminder job for LiveSession #{$session->id}: " . $e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }
}
