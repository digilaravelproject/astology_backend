<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CallService;
use App\Helpers\ApiResponse;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallEnded;
use App\Events\CallDismissed;
use App\Events\IceCandidateSent;
use App\Events\WebRtcSdpUpdated;
use App\Models\CallSession;
use App\Models\IceCandidate;
use App\Jobs\CallBillingTickJob;
use Exception;

class CallController extends Controller
{
    protected $callService;

    public function __construct(CallService $callService)
    {
        $this->callService = $callService;
    }

    // ─────────────────────────────────────────────────────────
    // INITIATE
    // ─────────────────────────────────────────────────────────

    public function initiateCall(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'offer'       => 'required|string',
        ]);

        try {
            $consumer = $request->user();
            $consumerId = $consumer->id;
            $session = $this->callService->initiateCall($consumerId, $request->provider_id);
            $session->load(['consumer', 'provider']);

            broadcast(new CallInitiated($session, [
                'id'                  => (int) $consumer->id,
                'name'                => $consumer->name,
                'phone'               => $consumer->phone,
                'gender'              => $consumer->gender,
                'date_of_birth'       => $consumer->date_of_birth ? ($consumer->date_of_birth instanceof \Carbon\Carbon ? $consumer->date_of_birth->toISOString() : $consumer->date_of_birth) : null,
                'time_of_birth'       => $consumer->time_of_birth,
                'place_of_birth'      => $consumer->place_of_birth,
                'latitude'            => $consumer->latitude ? (float) $consumer->latitude : null,
                'longitude'           => $consumer->longitude ? (float) $consumer->longitude : null,
                'city'                => $consumer->city,
                'country'             => $consumer->country,
                'languages'           => $consumer->languages ?? [],
                'profile_photo'       => $consumer->profile_photo,
                'profile_photo_url'   => \App\Helpers\MediaHelper::getUrl($consumer->profile_photo),
                'profile_completed'   => (bool) $consumer->profile_completed,
                'offer'               => $request->offer,
            ]));

            return ApiResponse::success(['session' => $session], 'Call initiated successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // ─────────────────────────────────────────────────────────
    // ACCEPT
    // ─────────────────────────────────────────────────────────

    public function acceptCall(Request $request, $sessionId)
    {
        $request->validate([
            'answer' => 'required|string',
        ]);

        try {
            $providerId = $request->user()->id;
            $session    = $this->callService->acceptCall($sessionId, $providerId);

            // Attach answer SDP to the in-memory object so the event payload carries it
            $session->answer = $request->answer;

            broadcast(new CallAccepted($session));

            return ApiResponse::success(['session' => $session], 'Call accepted successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // ─────────────────────────────────────────────────────────
    // REJECT  (astrologer refuses the incoming call)
    // Fix: was incorrectly firing CallEnded — now fires CallDismissed
    // ─────────────────────────────────────────────────────────

    public function rejectCall(Request $request, $sessionId)
    {
        try {
            $providerId = $request->user()->id;
            $session    = $this->callService->rejectCall($sessionId, $providerId);

            // CallDismissed broadcasts to BOTH channels so the user's ring screen closes
            broadcast(new CallDismissed($session, $providerId, 'rejected'));

            return ApiResponse::success(null, 'Call rejected');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // ─────────────────────────────────────────────────────────
    // CANCEL  (user withdraws their own call request)
    // ─────────────────────────────────────────────────────────

    public function cancelCall(Request $request, $sessionId)
    {
        try {
            $consumerId = $request->user()->id;
            $session    = $this->callService->cancelCall($sessionId, $consumerId);

            // CallDismissed broadcasts to BOTH channels so the astrologer's ring screen closes
            broadcast(new CallDismissed($session, $consumerId, 'missed'));

            return ApiResponse::success(null, 'Call cancelled successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // ─────────────────────────────────────────────────────────
    // END  (either party ends an active call)
    // ─────────────────────────────────────────────────────────

    public function endCall(Request $request, $sessionId)
    {
        try {
            $userId  = $request->user()->id;
            $session = $this->callService->endCall($sessionId, $userId);

            // Notify the OTHER participant their call has ended
            broadcast(new CallEnded($session, $userId));

            return ApiResponse::success(['session' => $session], 'Call ended successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    // ─────────────────────────────────────────────────────────
    // ICE CANDIDATE RELAY
    // ─────────────────────────────────────────────────────────

    public function sendIceCandidate(Request $request, $sessionId)
    {
        $request->validate([
            'candidate' => 'required|string',
        ]);

        try {
            $userId  = $request->user()->id;
            $session = $this->callService->getSession($sessionId);

            if (!$session || !in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                return ApiResponse::error('Invalid or expired session', 400);
            }

            // Security: only actual participants may relay ICE candidates
            if ($session->consumer_id == $userId) {
                $receiverId = $session->provider_id;
            } elseif ($session->provider_id == $userId) {
                $receiverId = $session->consumer_id;
            } else {
                return ApiResponse::error('Unauthorized participation in this session', 403);
            }

            IceCandidate::create([
                'call_session_id' => $sessionId,
                'sender_id'       => $userId,
                'receiver_id'     => $receiverId,
                'candidate'       => $request->candidate,
            ]);

            broadcast(new IceCandidateSent($session, $request->candidate, $receiverId));

            return ApiResponse::success(null, 'Candidate sent');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // UPDATE SDP (mid-call SDP re-exchange for reconnect)
    // ─────────────────────────────────────────────────────────

    public function updateSdp(Request $request, $sessionId)
    {
        $request->validate([
            'sdp'  => 'required|string',
            'type' => 'required|in:offer,answer',
        ]);

        try {
            $userId  = $request->user()->id;
            $session = $this->callService->getSession($sessionId);

            if (!$session || !in_array($session->status, ['accepted', 'ongoing'])) {
                return ApiResponse::error('Session is not active', 400);
            }

            if ($session->consumer_id != $userId && $session->provider_id != $userId) {
                return ApiResponse::error('Unauthorized', 403);
            }

            // Persist SDP for reconnect scenarios
            $column = ($userId == $session->consumer_id) ? 'consumer_sdp' : 'provider_sdp';
            $session->update([$column => $request->sdp]);

            // Relay to the other peer
            broadcast(new WebRtcSdpUpdated($session, $request->sdp, $userId, $request->type));

            return ApiResponse::success(null, 'SDP updated successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // PENDING CALLS (for astrologer to see unanswered rings on reconnect)
    // ─────────────────────────────────────────────────────────

    public function pending(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $sessions = CallSession::with([
                'consumer:id,name,phone,gender,date_of_birth,time_of_birth,place_of_birth,latitude,longitude,city,country,languages,profile_photo,profile_completed',
            ])
            ->where('provider_id', $userId)
            ->whereIn('status', ['initiated', 'ringing', 'waiting'])
            ->latest('id')
            ->get()
            ->map(function ($session) {
                $consumer = $session->consumer;
                $caller = $consumer ? [
                    'id'                  => (int) $consumer->id,
                    'name'                => $consumer->name,
                    'phone'               => $consumer->phone,
                    'gender'              => $consumer->gender,
                    'date_of_birth'       => $consumer->date_of_birth ? ($consumer->date_of_birth instanceof \Carbon\Carbon ? $consumer->date_of_birth->toISOString() : $consumer->date_of_birth) : null,
                    'time_of_birth'       => $consumer->time_of_birth,
                    'place_of_birth'      => $consumer->place_of_birth,
                    'latitude'            => $consumer->latitude ? (float) $consumer->latitude : null,
                    'longitude'           => $consumer->longitude ? (float) $consumer->longitude : null,
                    'city'                => $consumer->city,
                    'country'             => $consumer->country,
                    'languages'           => $consumer->languages ?? [],
                    'profile_photo'       => $consumer->profile_photo,
                    'profile_photo_url'   => \App\Helpers\MediaHelper::getUrl($consumer->profile_photo),
                    'profile_completed'   => (bool) $consumer->profile_completed,
                ] : null;

                return [
                    'id'          => (int) $session->id,
                    'status'      => $session->status,
                    'caller'      => $caller,
                    'consumer'    => $caller,
                    'created_at'  => $session->created_at?->toISOString(),
                    'expires_at'  => $session->created_at ? $session->created_at->addSeconds(60)->toISOString() : null,
                ];
            });

            return ApiResponse::success([
                'pending_calls' => $sessions->values(),
                'total'         => $sessions->count(),
            ], 'Pending calls retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // GET CURRENT ACTIVE CALL SESSION (for app resume / reconnect)
    // ─────────────────────────────────────────────────────────

    public function getCurrentSession(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $session = CallSession::with([
                'consumer:id,name,phone,gender,date_of_birth,time_of_birth,place_of_birth,latitude,longitude,city,country,languages,profile_photo,profile_completed',
                'provider:id,name,profile_photo',
                'provider.astrologer:user_id,call_rate_per_minute',
            ])
            ->where(function ($q) use ($userId) {
                $q->where('consumer_id', $userId)
                  ->orWhere('provider_id', $userId);
            })
            ->whereIn('status', ['initiated', 'ringing', 'accepted', 'waiting', 'ongoing'])
            ->orderByRaw("CASE WHEN status IN ('ongoing', 'accepted') THEN 1 WHEN status IN ('initiated', 'ringing', 'waiting') THEN 2 ELSE 3 END")
            ->latest('id')
            ->first();

            if (!$session) {
                return ApiResponse::success(null, 'No active call session found');
            }

            $isPrepaid = false;
            $remainingDurationSeconds = null;
            $packageInfo = null;

            if ($session->consumer) {
                $session->consumer->profile_photo_url = \App\Helpers\MediaHelper::getUrl($session->consumer->profile_photo);
            }
            if ($session->provider) {
                $session->provider->profile_photo_url = \App\Helpers\MediaHelper::getUrl($session->provider->profile_photo);
            }

            $subSession = \App\Models\PackageSubSession::where('call_session_id', $session->id)
                ->whereNull('ended_at')
                ->first();

            if ($subSession) {
                $isPrepaid = true;
                $purchase = $subSession->purchase;
                if ($purchase) {
                    $remainingDurationSeconds = (int) $purchase->remaining_duration;
                    if ($subSession->started_at) {
                        $elapsed = now()->diffInSeconds($subSession->started_at);
                        $remainingDurationSeconds = max(0, $remainingDurationSeconds - (int) $elapsed);
                    }
                }

                $packageInfo = [
                    'package_purchase_id'        => (int) $subSession->package_purchase_id,
                    'package_sub_session_id'     => (int) $subSession->id,
                    'remaining_duration_seconds' => $remainingDurationSeconds,
                ];
            }

            $session->billing_mode       = $isPrepaid ? 'prepaid' : 'normal';
            $session->is_normal          = !$isPrepaid;
            $session->is_prepaid         = $isPrepaid;
            $session->is_package_session = $isPrepaid;
            $session->package_info       = $packageInfo;

            return ApiResponse::success(
                [
                    'session'                    => $session,
                    'billing_mode'               => $isPrepaid ? 'prepaid' : 'normal',
                    'is_normal'                  => !$isPrepaid,
                    'is_prepaid'                 => $isPrepaid,
                    'is_package_session'         => $isPrepaid,
                    'session_type'               => 'call',
                    'package_info'               => $packageInfo,
                    'remaining_duration_seconds' => $remainingDurationSeconds,
                ],
                'Current active call session retrieved successfully'
            );

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // USER (CONSUMER) CALL HISTORY
    // ─────────────────────────────────────────────────────────

    public function getUserSessions(Request $request)
    {
        try {
            $userId  = $request->user()->id;
            $perPage = min((int) $request->query('per_page', 15), 50);

            $sessions = CallSession::with([
                'provider:id,name,profile_photo',
                'provider.astrologer:user_id,call_rate_per_minute',
            ])
            ->where('consumer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            return ApiResponse::success($sessions, 'Call sessions retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // ASTROLOGER (PROVIDER) CALL HISTORY
    // ─────────────────────────────────────────────────────────

    public function getAstrologerSessions(Request $request)
    {
        try {
            $userId  = $request->user()->id;
            $perPage = min((int) $request->query('per_page', 15), 50);

            $sessions = CallSession::with([
                'consumer:id,name,profile_photo,gender,date_of_birth,time_of_birth,place_of_birth,latitude,longitude',
            ])
            ->where('provider_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            $consumerIds = $sessions->pluck('consumer_id')->unique()->toArray();
            $assistanceSessions = \App\Models\ChatAssistanceSession::where(function($query) use ($userId, $consumerIds) {
                    $query->where('provider_id', $userId)
                          ->whereIn('consumer_id', $consumerIds);
                })
                ->orWhere(function($query) use ($userId, $consumerIds) {
                    $query->where('consumer_id', $userId)
                          ->whereIn('provider_id', $consumerIds);
                })
                ->get()
                ->mapWithKeys(function ($session) use ($userId) {
                    $otherId = ($session->consumer_id == $userId) ? $session->provider_id : $session->consumer_id;
                    return [$otherId => $session->id];
                });

            $sessions->getCollection()->transform(function ($session) use ($assistanceSessions) {
                $assistanceSessionId = $assistanceSessions[$session->consumer_id] ?? null;
                $session->chat_assistance_session_id = $assistanceSessionId;
                if ($session->consumer) {
                    $session->consumer->chat_assistance_session_id = $assistanceSessionId;
                }
                return $session;
            });

            return ApiResponse::success($sessions, 'Call sessions retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
