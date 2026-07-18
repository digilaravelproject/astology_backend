<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatAssistanceSession;
use App\Models\ChatAssistanceMessage;
use App\Models\ChatAssistanceEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupChatAssistanceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-chat-assistance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up chat assistance sessions, messages, and events older than 3 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threeDaysAgo = Carbon::now()->subDays(3);

        $this->info("Starting Chat Assistance cleanup for records older than: " . $threeDaysAgo->toDateTimeString());

        try {
            // Delete messages older than 3 days
            $deletedMessages = ChatAssistanceMessage::where('created_at', '<', $threeDaysAgo)->delete();
            $this->info("Deleted {$deletedMessages} messages.");

            // Delete events older than 3 days
            $deletedEvents = ChatAssistanceEvent::where('created_at', '<', $threeDaysAgo)->delete();
            $this->info("Deleted {$deletedEvents} events.");

            // Delete sessions older than 3 days (if they have no messages left)
            $deletedSessions = ChatAssistanceSession::whereDoesntHave('messages')
                ->where('created_at', '<', $threeDaysAgo)
                ->delete();
            $this->info("Deleted {$deletedSessions} empty sessions.");

            Log::info("Chat Assistance cleanup ran successfully. Deleted Messages: {$deletedMessages}, Events: {$deletedEvents}, Sessions: {$deletedSessions}.");
        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            Log::error("Chat Assistance cleanup failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
