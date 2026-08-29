<?php

namespace App\Services;

use App\Events\CallDismissed;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatDismissed;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\ChatQueueUpdated;
use App\Events\PackageSessionStateUpdated;
use App\Events\PackageSessionTerminated;
use App\Helpers\MediaHelper;
use App\Jobs\TerminatePackageSessionJob;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SessionTimerService
 * 
 * Manages package sub-session lifecycle:
 * - Sub-session Initiation & Channel Association
 * - Timer Activation upon Astrologer Acceptance
 * - Sub-session Termination, Billing Duration Deduction & Cleanup
 */
class SessionTimerService
{
    public function __construct(
        protected PresenceService $presenceService,
        protected ChatService $chatService,
        protected CallService $callService
    ) {}

    // =========================================================================
    // 1. SUB-SESSION LIFECYCLE MANAGEMENT
    // =========================================================================

    /**
     * Start a new package sub-session (chat or call).
     *
     * @throws Exception
     */
    public function startSubSession(int $userId, int $astrologerId, string $mode, ?string $question = null, ?string $offer = null): array
    {
        try {
            $purchase = PackagePurchase::where('user_id', $userId)
                ->where('astrologer_id', $astrologerId)
                ->where('status', 'active')
                ->where('remaining_duration', '>', 0)
                ->first();

            if (!$purchase) {
                throw new Exception("You do not have an active package purchase for this astrologer. Please purchase a package first.", 422);
            }

            $hasActiveSubSession = PackageSubSession::whereNotNull('started_at')
                ->whereNull('ended_at')
                ->whereHas('purchase', function ($q) use ($userId, $astrologerId) {
                    $q->where(function ($subQ) use ($userId, $astrologerId) {
                        $subQ->where('user_id', $userId)
                             ->orWhere('astrologer_id', $userId)
                             ->orWhere('user_id', $astrologerId)
                             ->orWhere('astrologer_id', $astrologerId);
                    });
                })
                ->exists();

            if ($hasActiveSubSession) {
                throw new Exception("A package sub-session is already active for you or the astrologer.", 422);
            }

            // Close any abandoned ringing-phase sub-sessions before starting fresh
            PackageSubSession::whereNull('started_at')
                ->whereNull('ended_at')
                ->whereHas('purchase', function ($q) use ($userId, $astrologerId) {
                    $q->whereIn('user_id', [$userId, $astrologerId])
                        ->orWhereIn('astrologer_id', [$userId, $astrologerId]);
                })
                ->update(['ended_at' => now()]);

            if ($mode === 'chat') {
                $linkedSession = $this->chatService->initiateChat($userId, $astrologerId, $question, true);
                $linkedSession->load(['consumer', 'provider']);

                $user = User::find($userId);
                if ($user) {
                    broadcast(new ChatInitiated($linkedSession, $user));
                    broadcast(new ChatQueueUpdated($linkedSession->provider_id, $linkedSession, 'initiated'));
                }
            } else {
                $linkedSession = $this->callService->initiateCall($userId, $astrologerId, true);
                $linkedSession->load(['consumer', 'provider']);

                $user = User::find($userId);
                if ($user) {
                    broadcast(new CallInitiated($linkedSession, [
                        'id'                => (int) $user->id,
                        'name'              => $user->name,
                        'phone'             => $user->phone,
                        'gender'            => $user->gender,
                        'date_of_birth'     => $user->date_of_birth ? ($user->date_of_birth instanceof Carbon ? $user->date_of_birth->toISOString() : $user->date_of_birth) : null,
                        'time_of_birth'     => $user->time_of_birth,
                        'place_of_birth'    => $user->place_of_birth,
                        'latitude'          => $user->latitude ? (float) $user->latitude : null,
                        'longitude'         => $user->longitude ? (float) $user->longitude : null,
                        'city'              => $user->city,
                        'country'           => $user->country,
                        'languages'         => $user->languages ?? [],
                        'profile_photo'     => $user->profile_photo,
                        'profile_photo_url' => MediaHelper::getUrl($user->profile_photo),
                        'profile_completed' => (bool) $user->profile_completed,
                        'offer'             => $offer ?? 'audio',
                    ]));
                }
            }

            $subSession = DB::transaction(function () use ($purchase, $mode, $linkedSession) {
                $purchase->lockForUpdate()->first();

                $data = [
                    'package_purchase_id' => $purchase->id,
                    'mode'                => $mode,
                    'started_at'          => null,
                    'ended_at'            => null,
                    'duration_used'       => 0,
                ];

                if ($mode === 'chat') {
                    $data['chat_session_id'] = $linkedSession->id;
                } else {
                    $data['call_session_id'] = $linkedSession->id;
                }

                return PackageSubSession::create($data);
            });

            $result = ['sub_session' => $subSession];

            if ($mode === 'chat') {
                $result['chat_session'] = $linkedSession;
            } else {
                $result['call_session'] = $linkedSession;
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Starting package sub-session failed.', [
                'user_id'       => $userId,
                'astrologer_id' => $astrologerId,
                'mode'          => $mode,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activate the sub-session timer once the astrologer accepts the chat or call.
     *
     * @throws Exception
     */
    public function activateSubSessionTimer(int $subSessionId): PackageSubSession
    {
        try {
            return DB::transaction(function () use ($subSessionId) {
                $subSession = PackageSubSession::where('id', $subSessionId)
                    ->lockForUpdate()
                    ->first();

                if (!$subSession) {
                    throw new Exception("Sub-session #{$subSessionId} not found.", 404);
                }

                if (!is_null($subSession->started_at)) {
                    return $subSession;
                }

                $purchase = PackagePurchase::where('id', $subSession->package_purchase_id)
                    ->lockForUpdate()
                    ->first();

                if (!$purchase) {
                    throw new Exception("Parent package purchase not found for sub-session #{$subSessionId}.", 404);
                }

                $subSession->started_at = now();
                $subSession->save();

                TerminatePackageSessionJob::dispatch($subSession->id)
                    ->delay(now()->addSeconds($purchase->remaining_duration));

                broadcast(new PackageSessionStateUpdated(
                    $subSession->toBannerArray($purchase->remaining_duration),
                    $purchase->user_id,
                    $purchase->astrologer_id
                ));

                return $subSession;
            });

        } catch (Exception $e) {
            Log::error('Activating package sub-session timer failed.', [
                'sub_session_id' => $subSessionId,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * End an active sub-session and deduct duration from the package.
     *
     * @throws Exception
     */
    public function endSubSession(int $subSessionId, ?int $userId = null, bool $isForceTerminated = false): PackageSubSession
    {
        try {
            $eventsToBroadcast = [];

            $subSession = DB::transaction(function () use ($subSessionId, $userId, $isForceTerminated, &$eventsToBroadcast) {
                $subSession = PackageSubSession::where('id', $subSessionId)
                    ->lockForUpdate()
                    ->first();

                if (!$subSession) {
                    throw new Exception("Sub-session #{$subSessionId} not found.", 404);
                }

                if (!is_null($subSession->ended_at)) {
                    return $subSession;
                }

                $purchase = PackagePurchase::where('id', $subSession->package_purchase_id)
                    ->lockForUpdate()
                    ->first();

                if (!$purchase) {
                    throw new Exception("Parent package purchase not found for sub-session #{$subSessionId}.", 404);
                }

                if ($userId && $purchase->user_id !== $userId && $purchase->astrologer_id !== $userId) {
                    throw new Exception("Unauthorized. You are not a participant in this package session.", 403);
                }

                $actorId         = $userId ?? $purchase->user_id;
                $endTime         = now();
                $durationSeconds = $subSession->started_at
                    ? (int) $subSession->started_at->diffInSeconds($endTime)
                    : 0;
                $durationUsed    = (int) min(max($durationSeconds, 0), $purchase->remaining_duration);

                $subSession->ended_at      = $endTime;
                $subSession->duration_used = $durationUsed;
                $subSession->save();

                $purchase->remaining_duration = max(0, (int) ($purchase->remaining_duration - $durationUsed));
                if ($purchase->remaining_duration <= 0) {
                    $purchase->status = 'exhausted';
                }
                $purchase->save();

                // 1. Close linked chat if present
                if ($subSession->chat_session_id) {
                    try {
                        $chatBefore = ChatSession::find($subSession->chat_session_id);
                        $wasInitiated = $chatBefore && in_array($chatBefore->status, ['initiated', 'waiting']);
                        $linkedChat = $this->chatService->endChat($subSession->chat_session_id);
                        if ($wasInitiated) {
                            $eventsToBroadcast[] = new ChatDismissed($linkedChat, $actorId, 'cancelled');
                        } else {
                            $eventsToBroadcast[] = new ChatEnded($linkedChat, $actorId);
                        }
                        $eventsToBroadcast[] = new ChatQueueUpdated($linkedChat->provider_id, $linkedChat, 'ended');
                    } catch (Exception $e) {
                        Log::warning('Could not end linked chat session during sub-session end.', [
                            'sub_session_id'  => $subSessionId,
                            'chat_session_id' => $subSession->chat_session_id,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                }

                // 2. Close linked call if present
                if ($subSession->call_session_id) {
                    try {
                        $callBefore = CallSession::find($subSession->call_session_id);
                        $wasRinging = $callBefore && in_array($callBefore->status, ['initiated', 'ringing']);
                        $linkedCall = $this->callService->endCall($subSession->call_session_id);
                        if ($wasRinging) {
                            $eventsToBroadcast[] = new CallDismissed($linkedCall, $actorId, 'cancelled');
                        } else {
                            $eventsToBroadcast[] = new CallEnded($linkedCall, $actorId);
                        }
                    } catch (Exception $e) {
                        Log::warning('Could not end linked call session during sub-session end.', [
                            'sub_session_id'  => $subSessionId,
                            'call_session_id' => $subSession->call_session_id,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                }

                // 3. Clean up and collect events for any lingering chat or call sessions
                $lingeringEvents = $this->closeAndBroadcastLingeringSessions($purchase->user_id, $purchase->astrologer_id, $actorId, $endTime);
                $eventsToBroadcast = array_merge($eventsToBroadcast, $lingeringEvents);

                // 4. Free presence and flush catalog cache
                $this->cleanupPresenceAndCache($purchase->user_id, $purchase->astrologer_id);

                $eventsToBroadcast[] = new PackageSessionStateUpdated(
                    $subSession->toBannerArray($purchase->remaining_duration),
                    $purchase->user_id,
                    $purchase->astrologer_id
                );

                if ($isForceTerminated || $purchase->status === 'exhausted') {
                    $msg = $isForceTerminated
                        ? "Your package session was forcefully terminated due to time expiration."
                        : "Your package session has exhausted all remaining balance.";

                    $eventsToBroadcast[] = new PackageSessionTerminated($purchase, $msg, $subSession->mode);
                }

                return $subSession;
            });

            // Dispatch all collected events outside transaction
            foreach ($eventsToBroadcast as $event) {
                broadcast($event);
            }

            return $subSession;

        } catch (Exception $e) {
            Log::error('Ending package sub-session failed.', [
                'sub_session_id' => $subSessionId,
                'user_id'        => $userId,
                'force'          => $isForceTerminated,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the currently active sub-session for a user (as consumer or astrologer).
     */
    public function getActiveSubSession(int $userId): ?PackageSubSession
    {
        return PackageSubSession::whereNotNull('started_at')
            ->whereNull('ended_at')
            ->whereHas('purchase', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('astrologer_id', $userId);
            })
            ->first();
    }

    // =========================================================================
    // 2. INTERNAL TEARDOWN HELPERS
    // =========================================================================

    /**
     * Close lingering sessions and generate dismissal/end events.
     */
    protected function closeAndBroadcastLingeringSessions(int $userId, int $astrologerId, int $actorId, Carbon $endedAt): array
    {
        $events = [];

        $lingeringChats = ChatSession::where('consumer_id', $userId)
            ->where('provider_id', $astrologerId)
            ->whereIn('status', ['initiated', 'waiting', 'accepted', 'ongoing', 'active'])
            ->get();

        foreach ($lingeringChats as $chat) {
            $wasInitiated = in_array($chat->status, ['initiated', 'waiting']);
            $chat->update(['status' => $wasInitiated ? 'cancelled' : 'completed', 'ended_at' => $endedAt]);
            if ($wasInitiated) {
                $events[] = new ChatDismissed($chat, $actorId, 'cancelled');
            } else {
                $events[] = new ChatEnded($chat, $actorId);
            }
        }

        $lingeringCalls = CallSession::where('consumer_id', $userId)
            ->where('provider_id', $astrologerId)
            ->whereIn('status', ['initiated', 'ringing', 'waiting', 'accepted', 'ongoing', 'active'])
            ->get();

        foreach ($lingeringCalls as $call) {
            $wasRinging = in_array($call->status, ['initiated', 'ringing']);
            $call->update(['status' => $wasRinging ? 'missed' : 'completed', 'ended_at' => $endedAt]);
            if ($wasRinging) {
                $events[] = new CallDismissed($call, $actorId, 'cancelled');
            } else {
                $events[] = new CallEnded($call, $actorId);
            }
        }

        return $events;
    }

    /**
     * Free presence status and flush astrologer catalog cache.
     */
    protected function cleanupPresenceAndCache(int $userId, int $astrologerId): void
    {
        $this->presenceService->setFree($userId);
        $this->presenceService->setFree($astrologerId);

        User::whereIn('id', [$userId, $astrologerId])
            ->update(['is_busy' => false, 'busy_session_id' => null]);

        AstrologerService::flushCatalogCache();
    }
}
