<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Jobs\TerminatePackageSessionJob;
use App\Events\PackageSubSessionStarted;
use App\Events\PackageSubSessionEnded;
use App\Events\PackageSessionTerminated;
use App\Events\ChatInitiated;
use App\Events\ChatQueueUpdated;
use App\Events\CallInitiated;
use App\Events\ChatEnded;
use App\Events\CallEnded;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

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
     * Start a new sub-session (chat/call) using an active package.
     * Internally creates a real ChatSession or CallSession via existing services.
     *
     * @param int $userId
     * @param int $astrologerId
     * @param string $mode  'chat' | 'call'
     * @param string|null $question  Optional question for chat
     * @return array{ sub_session: PackageSubSession, chat_session?: mixed, call_session?: mixed }
     * @throws Exception
     */
    public function startSubSession(int $userId, int $astrologerId, string $mode, ?string $question = null): array
    {
        // Step 1: Validate active package purchase (outside main transaction — avoids nested tx issues)
        $purchase = PackagePurchase::where('user_id', $userId)
            ->where('astrologer_id', $astrologerId)
            ->where('status', 'active')
            ->where('remaining_duration', '>', 0)
            ->first();

        if (!$purchase) {
            throw new Exception("You do not have an active package purchase for this astrologer. Please purchase a package first.", 422);
        }

        // Step 2: Verify no other package sub-session is currently active for these users
        $hasActiveSubSession = PackageSubSession::whereNull('ended_at')
            ->whereHas('purchase', function ($query) use ($userId, $astrologerId) {
                $query->whereIn('user_id', [$userId, $astrologerId])
                    ->orWhereIn('astrologer_id', [$userId, $astrologerId]);
            })
            ->exists();

        if ($hasActiveSubSession) {
            throw new Exception("A package sub-session is already active for you or the astrologer.", 422);
        }

        // Step 3: Internally trigger the existing chat or call service.
        // This creates the real ChatSession / CallSession, sends WS notification to astrologer.
        // Wallet balance check is bypassed inside these services when an active package exists.
        if ($mode === 'chat') {
            $linkedSession = $this->chatService->initiateChat($userId, $astrologerId, $question);
            
            // Broadcast ChatInitiated with full consumer details
            $user = \App\Models\User::find($userId);
            if ($user) {
                broadcast(new ChatInitiated($linkedSession, $user));
                broadcast(new ChatQueueUpdated($linkedSession->provider_id, $linkedSession, 'initiated'));
            }
        } else {
            $linkedSession = $this->callService->initiateCall($userId, $astrologerId);
            
            // Broadcast CallInitiated with full consumer details
            $user = \App\Models\User::find($userId);
            if ($user) {
                broadcast(new CallInitiated($linkedSession, [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'profile_photo' => \App\Helpers\MediaHelper::getFullUrl($user->profile_photo),
                    'offer'         => 'audio',
                ]));
            }
        }

        // Step 4: Create PackageSubSession record and link to the real session
        $subSession = DB::transaction(function () use ($purchase, $mode, $linkedSession) {
            $purchase->lockForUpdate()->first(); // re-lock for duration read

            $subSessionData = [
                'package_purchase_id' => $purchase->id,
                'mode'                => $mode,
                'started_at'          => now(),
                'ended_at'            => null,
                'duration_used'       => 0,
            ];

            if ($mode === 'chat') {
                $subSessionData['chat_session_id'] = $linkedSession->id;
            } else {
                $subSessionData['call_session_id'] = $linkedSession->id;
            }

            $subSession = PackageSubSession::create($subSessionData);

            // Dispatch auto-terminate job after remaining_duration seconds
            TerminatePackageSessionJob::dispatch($subSession->id)
                ->delay(now()->addSeconds($purchase->remaining_duration));

            // Broadcast package-specific WebSocket event (countdown timer for Flutter)
            broadcast(new PackageSubSessionStarted($subSession, $purchase->remaining_duration));

            return $subSession;
        });

        $result = ['sub_session' => $subSession];

        if ($mode === 'chat') {
            $result['chat_session'] = $linkedSession;
        } else {
            $result['call_session'] = $linkedSession;
        }

        return $result;
    }

    /**
     * End an active sub-session.
     * Also ends the linked ChatSession or CallSession via existing services.
     *
     * @param int $subSessionId
     * @param int|null $userId
     * @param bool $isForceTerminated
     * @return PackageSubSession
     * @throws Exception
     */
    public function endSubSession(int $subSessionId, ?int $userId = null, bool $isForceTerminated = false): PackageSubSession
    {
        return DB::transaction(function () use ($subSessionId, $userId, $isForceTerminated) {
            $subSession = PackageSubSession::where('id', $subSessionId)
                ->lockForUpdate()
                ->first();

            if (!$subSession) {
                throw new Exception("Sub-session not found.", 404);
            }

            if (!is_null($subSession->ended_at)) {
                return $subSession; // Already ended — idempotent
            }

            $purchase = PackagePurchase::where('id', $subSession->package_purchase_id)
                ->lockForUpdate()
                ->first();

            if (!$purchase) {
                throw new Exception("Parent package purchase not found.", 404);
            }

            // Authorize if ended by user
            if ($userId && $purchase->user_id !== $userId && $purchase->astrologer_id !== $userId) {
                throw new Exception("Unauthorized. You are not a participant in this package session.", 403);
            }

            $endTime = now();
            $durationSeconds = (int) $subSession->started_at->diffInSeconds($endTime);
            if ($durationSeconds < 0) {
                $durationSeconds = 0;
            }

            // Clamp to remaining package duration
            $durationUsed = (int) min($durationSeconds, $purchase->remaining_duration);

            // Update sub-session record
            $subSession->ended_at      = $endTime;
            $subSession->duration_used = $durationUsed;
            $subSession->save();

            // Deduct from package purchase
            $purchase->remaining_duration -= $durationUsed;
            if ($purchase->remaining_duration <= 0) {
                $purchase->remaining_duration = 0;
                $purchase->status = 'exhausted';
            }
            $purchase->save();

            // End the linked real session (chat or call) via existing services
            // This handles presence reset, billing cleanup, and existing end events internally.
            if ($subSession->chat_session_id) {
                try {
                    $linkedChat = $this->chatService->endChat($subSession->chat_session_id);
                    // Broadcast standard ChatEnded event so standard chat feeds close on both sides
                    broadcast(new ChatEnded($linkedChat, $userId ?? $purchase->user_id));
                    broadcast(new \App\Events\ChatQueueUpdated($linkedChat->provider_id, $linkedChat, 'ended'));
                } catch (Exception $e) {
                    // Session may already be ended — safe to swallow
                }
            } elseif ($subSession->call_session_id) {
                try {
                    $linkedCall = $this->callService->endCall($subSession->call_session_id);
                    // Broadcast standard CallEnded event so standard call screens close on both sides
                    broadcast(new CallEnded($linkedCall, $userId ?? $purchase->user_id));
                } catch (Exception $e) {
                    // Session may already be ended — safe to swallow
                }
            } else {
                // Fallback: manually free presence if no linked session
                $this->presenceService->setFree($purchase->user_id);
                $this->presenceService->setFree($purchase->astrologer_id);
            }

            // Broadcast package-specific WebSocket event
            broadcast(new PackageSubSessionEnded($subSession, $purchase->remaining_duration, $userId));

            // Broadcast force termination if time ran out
            if ($isForceTerminated || $purchase->status === 'exhausted') {
                $msg = $isForceTerminated
                    ? "Your package session was forcefully terminated due to time expiration."
                    : "Your package session has exhausted all remaining balance.";

                broadcast(new PackageSessionTerminated($purchase, $msg, $subSession->mode));
            }

            return $subSession;
        });
    }

    /**
     * Get the currently active sub-session for a user (as consumer or astrologer).
     */
    public function getActiveSubSession(int $userId): ?PackageSubSession
    {
        return PackageSubSession::whereNull('ended_at')
            ->whereHas('purchase', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('astrologer_id', $userId);
            })
            ->first();
    }
}

