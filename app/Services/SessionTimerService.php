<?php

namespace App\Services;

use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\ChatQueueUpdated;
use App\Events\PackageSessionStateUpdated;
use App\Events\PackageSessionTerminated;
use App\Jobs\TerminatePackageSessionJob;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionTimerService
{
    protected $presenceService;
    protected $chatService;
    protected $callService;

    public function __construct(
        PresenceService $presenceService,
        ChatService $chatService,
        CallService $callService
    ) {
        $this->presenceService = $presenceService;
        $this->chatService     = $chatService;
        $this->callService     = $callService;
    }

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

            // Close any abandoned ringing-phase sub-sessions before starting fresh.
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

                $user = \App\Models\User::find($userId);
                if ($user) {
                    broadcast(new ChatInitiated($linkedSession, $user));
                    broadcast(new ChatQueueUpdated($linkedSession->provider_id, $linkedSession, 'initiated'));
                }
            } else {
                $linkedSession = $this->callService->initiateCall($userId, $astrologerId, true);
                $linkedSession->load(['consumer', 'provider']);

                $user = \App\Models\User::find($userId);
                if ($user) {
                    broadcast(new CallInitiated($linkedSession, [
                        'id'                  => (int) $user->id,
                        'name'                => $user->name,
                        'phone'               => $user->phone,
                        'gender'              => $user->gender,
                        'date_of_birth'       => $user->date_of_birth ? ($user->date_of_birth instanceof \Carbon\Carbon ? $user->date_of_birth->toISOString() : $user->date_of_birth) : null,
                        'time_of_birth'       => $user->time_of_birth,
                        'place_of_birth'      => $user->place_of_birth,
                        'latitude'            => $user->latitude ? (float) $user->latitude : null,
                        'longitude'           => $user->longitude ? (float) $user->longitude : null,
                        'city'                => $user->city,
                        'country'             => $user->country,
                        'languages'           => $user->languages ?? [],
                        'profile_photo'       => $user->profile_photo,
                        'profile_photo_url'   => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                        'profile_completed'   => (bool) $user->profile_completed,
                        'offer'               => $offer ?? 'audio',
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

                $endTime         = now();
                $durationSeconds = $subSession->started_at
                    ? (int) $subSession->started_at->diffInSeconds($endTime)
                    : 0;
                $durationUsed    = (int) min(max($durationSeconds, 0), $purchase->remaining_duration);

                $subSession->ended_at      = $endTime;
                $subSession->duration_used = $durationUsed;
                $subSession->save();

                $purchase->remaining_duration -= $durationUsed;
                if ($purchase->remaining_duration <= 0) {
                    $purchase->remaining_duration = 0;
                    $purchase->status = 'exhausted';
                }
                $purchase->save();

                if ($subSession->chat_session_id) {
                    try {
                        $linkedChat = $this->chatService->endChat($subSession->chat_session_id);
                        $eventsToBroadcast[] = new ChatEnded($linkedChat, $userId ?? $purchase->user_id);
                        $eventsToBroadcast[] = new ChatQueueUpdated($linkedChat->provider_id, $linkedChat, 'ended');
                    } catch (Exception $e) {
                        Log::warning('Could not end linked chat session during sub-session end.', [
                            'sub_session_id'  => $subSessionId,
                            'chat_session_id' => $subSession->chat_session_id,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                } elseif ($subSession->call_session_id) {
                    try {
                        $linkedCall = $this->callService->endCall($subSession->call_session_id);
                        $eventsToBroadcast[] = new CallEnded($linkedCall, $userId ?? $purchase->user_id);
                    } catch (Exception $e) {
                        Log::warning('Could not end linked call session during sub-session end.', [
                            'sub_session_id'  => $subSessionId,
                            'call_session_id' => $subSession->call_session_id,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                } else {
                    $this->presenceService->setFree($purchase->user_id);
                    $this->presenceService->setFree($purchase->astrologer_id);
                }

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
}
