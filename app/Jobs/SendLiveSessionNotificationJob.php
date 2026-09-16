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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SendLiveSessionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [15, 60];
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
            $astrologerId = $astrologer->id;
            $astrologerUserId = $astrologer->user_id;

            // RULE 2: Anti-Spam & Cooldown Check (10 minutes) for live alerts
            if ($this->notificationType === 'live') {
                $cooldownKey = "astro_live_notif_cooldown_{$astrologerId}";
                if (Cache::has($cooldownKey)) {
                    Log::info("SendLiveSessionNotificationJob: Live notification suppressed by 10-minute cooldown for Astrologer #{$astrologerId} (Session #{$session->id}).");
                    $session->update(['is_live_notified' => true]);
                    return;
                }
            }

            $astrologerUser = $astrologer->user;
            $astrologerName = $astrologerUser?->name ?? 'Astrologer';
            $astrologerAvatar = $astrologerUser?->profile_photo
                ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo)
                : $astrologer->profile_photo;

            // RULE 1: Strict Audience Isolation
            // Strictly target active followers only: is_liked = true, is_blocked = false, and exclude the astrologer themselves.
            $targetUserIds = DB::table('astrologer_communities')
                ->where('astrologer_id', $astrologerId)
                ->where('is_liked', true)
                ->where('is_blocked', false)
                ->where('user_id', '!=', $astrologerUserId)
                ->pluck('user_id')
                ->unique()
                ->values()
                ->toArray();

            // Zero followers: Gracefully complete with 0 dispatches. Strictly NEVER fallback to all users.
            if (empty($targetUserIds)) {
                Log::info("SendLiveSessionNotificationJob: No active followers found for Astrologer #{$astrologerId}. Push delivery skipped.");
                if ($this->notificationType === 'live') {
                    $session->update(['is_live_notified' => true]);
                    Cache::put("astro_live_notif_cooldown_{$astrologerId}", now()->toIso8601String(), now()->addMinutes(10));
                }
                return;
            }

            // RULE 4: Build Standardized Deep-Linking Push Notification Payload
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

            // RULE 3: Micro-Batch Queue Dispatching (250 per chunk)
            $chunks = array_chunk($targetUserIds, 250);
            foreach ($chunks as $chunk) {
                NotificationService::sendToUsers($chunk, $payload, saveInApp: true);
            }

            // Mark notification flags & activate anti-spam cooldown
            if ($this->notificationType === 'live') {
                $session->update(['is_live_notified' => true]);
                Cache::put("astro_live_notif_cooldown_{$astrologerId}", now()->toIso8601String(), now()->addMinutes(10));
            } elseif ($this->notificationType === 'scheduled') {
                $session->update(['is_scheduled_notified' => true]);
            } elseif ($this->notificationType === 'reminder') {
                $session->update(['is_reminder_notified' => true]);
            }

            Log::info("SendLiveSessionNotificationJob: Dispatched {$this->notificationType} notifications for LiveSession #{$session->id} to " . count($targetUserIds) . " followers.");

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
