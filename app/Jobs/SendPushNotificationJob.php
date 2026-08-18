<?php

namespace App\Jobs;

use App\Models\BroadcastNotification;
use App\Services\Notification\FcmChannelDriver;
use App\Services\Notification\PushNotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 30];
    public int $timeout = 60;

    protected array $tokens;
    protected PushNotificationPayload $payload;
    protected ?int $broadcastId;

    /**
     * Create a new job instance.
     *
     * @param array $tokens Array of FCM device tokens
     * @param PushNotificationPayload $payload
     * @param int|null $broadcastId Optional BroadcastNotification ID to track campaign stats
     */
    public function __construct(array $tokens, PushNotificationPayload $payload, ?int $broadcastId = null)
    {
        $this->tokens = array_values(array_unique(array_filter($tokens)));
        $this->payload = $payload;
        $this->broadcastId = $broadcastId;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(FcmChannelDriver $fcmDriver): void
    {
        if (empty($this->tokens)) {
            return;
        }

        if (!$fcmDriver->isConfigured()) {
            Log::warning('SendPushNotificationJob: FCM driver is not configured or disabled. Skipping push delivery.');
            return;
        }

        $results = $fcmDriver->sendToTokens($this->tokens, $this->payload);

        // Update broadcast statistics if this was an admin broadcast
        if ($this->broadcastId) {
            $broadcast = BroadcastNotification::find($this->broadcastId);
            if ($broadcast) {
                $broadcast->increment('successful_count', $results['successful']);
                $broadcast->increment('failed_count', $results['failed']);
                
                if ($broadcast->successful_count + $broadcast->failed_count >= $broadcast->total_recipients) {
                    $broadcast->update(['status' => 'completed']);
                }
            }
        }

        Log::info("Push notification sent. Type: {$this->payload->type}, Total: {$results['total']}, Success: {$results['successful']}, Failed: {$results['failed']}");
    }

    /**
     * Handle job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SendPushNotificationJob Failed permanently: ' . ($exception ? $exception->getMessage() : 'Unknown error'));

        if ($this->broadcastId) {
            $broadcast = BroadcastNotification::find($this->broadcastId);
            if ($broadcast) {
                $broadcast->update([
                    'status' => 'failed',
                    'error_message' => $exception ? $exception->getMessage() : 'Job failed permanently',
                ]);
            }
        }
    }
}
