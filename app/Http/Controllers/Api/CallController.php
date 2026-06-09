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
            $consumerId = $request->user()->id;
            $session = $this->callService->initiateCall($consumerId, $request->provider_id);

            broadcast(new CallInitiated($session, [
                'id'            => $request->user()->id,
                'name'          => $request->user()->name,
                'profile_photo' => $request->user()->profile_photo_url,
                'offer'         => $request->offer,
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
            broadcast(new CallDismissed($session, $consumerId, 'cancelled'));

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
    // GET CURRENT ACTIVE CALL SESSION (for app resume / reconnect)
    // ─────────────────────────────────────────────────────────

    public function getCurrentSession(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $session = CallSession::with([
                'consumer:id,name,profile_photo',
                'provider:id,name,profile_photo',
                'provider.astrologer:user_id,call_rate_per_minute',
            ])
            ->where(function ($q) use ($userId) {
                $q->where('consumer_id', $userId)
                  ->orWhere('provider_id', $userId);
            })
            ->whereIn('status', ['initiated', 'ringing', 'accepted', 'waiting', 'ongoing'])
            ->latest()
            ->first();

            return ApiResponse::success(
                ['session' => $session],
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
                'consumer:id,name,profile_photo',
            ])
            ->where('provider_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            return ApiResponse::success($sessions, 'Call sessions retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
