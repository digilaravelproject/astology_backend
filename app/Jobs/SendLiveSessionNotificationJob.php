<?php

namespace App\Jobs;

use App\Models\LiveSession;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SendLiveSessionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param int $liveSessionId
     * @param string $notificationType 'live', 'scheduled', 'reminder'
     * @param string|null $customTitle
     * @param string|null $customBody
     */
    public function __construct(
        public int $liveSessionId,
        public string $notificationType = 'live',
        public ?string $customTitle = null,
        public ?string $customBody = null
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $session = LiveSession::with(['astrologer.user'])->find($this->liveSessionId);
            if (!$session || !$session->astrologer) {
                Log::warning("SendLiveSessionNotificationJob: LiveSession #{$this->liveSessionId} or Astrologer not found.");
                return;
            }

            $astrologer = $session->astrologer;
            $astrologerUser = $astrologer->user;
            $astrologerName = $astrologerUser?->name ?? 'Astrologer';
            $astrologerAvatar = $astrologerUser?->profile_photo
                ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo)
                : $astrologer->profile_photo;

            $astrologerId = $astrologer->id;
            $astrologerUserId = $astrologer->user_id;

            // 1. High-Performance SQL UNION Audience Deduplication
            $targetUserIds = DB::table(function ($query) use ($astrologerId, $astrologerUserId) {
                $query->select('user_id')
                    ->from('astrologer_communities')
                    ->where('astrologer_id', $astrologerId)
                    ->where('is_liked', true)
                    ->where('is_blocked', false)
                    ->union(
                        DB::table('chat_sessions')
                            ->select('consumer_id as user_id')
                            ->where('provider_id', $astrologerUserId)
                    )
                    ->union(
                        DB::table('call_sessions')
                            ->select('consumer_id as user_id')
                            ->where('provider_id', $astrologerUserId)
                    );
            }, 'audience')
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $astrologerUserId)
            ->whereNotIn('user_id', function ($query) use ($astrologerId) {
                $query->select('user_id')
                    ->from('astrologer_communities')
                    ->where('astrologer_id', $astrologerId)
                    ->where('is_blocked', true);
            })
            ->pluck('user_id')
            ->toArray();

            if (empty($targetUserIds)) {
                Log::info("SendLiveSessionNotificationJob: No eligible audience found for Astrologer #{$astrologerId} LiveSession #{$session->id}.");
                return;
            }

            // 2. Build Deep-Linking Push Notification Payload
            $payload = PushNotificationPayload::forLiveSession(
                sessionId: $session->id,
                astrologerId: $astrologerId,
                astrologerName: $astrologerName,
                astrologerAvatar: $astrologerAvatar,
                status: $this->notificationType,
                title: $this->customTitle ?? '',
                body: $this->customBody ?? '',
                channelName: $session->room_uuid ?: "live_session_{$session->id}"
            );

            // 3. Chunked Dispatching to prevent memory overload
            $chunks = array_chunk($targetUserIds, 250);
            foreach ($chunks as $chunk) {
                NotificationService::sendToUsers($chunk, $payload, saveInApp: true);
            }

            // 4. Mark notification flag on LiveSession model
            if ($this->notificationType === 'live') {
                $session->update(['is_live_notified' => true]);
            } elseif ($this->notificationType === 'scheduled') {
                $session->update(['is_scheduled_notified' => true]);
            } elseif ($this->notificationType === 'reminder') {
                $session->update(['is_reminder_notified' => true]);
            }

            Log::info("SendLiveSessionNotificationJob: Dispatched {$this->notificationType} notifications for LiveSession #{$session->id} to " . count($targetUserIds) . " users.");

        } catch (Exception $e) {
            Log::error("SendLiveSessionNotificationJob Failed: " . $e->getMessage(), [
                'session_id' => $this->liveSessionId,
                'type'       => $this->notificationType,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
