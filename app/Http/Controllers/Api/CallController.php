<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CallService;
use App\Helpers\ApiResponse;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallEnded;
use App\Events\IceCandidateSent;
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

    public function initiateCall(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'offer' => 'required|string'
        ]);

        try {
            $consumerId = $request->user()->id;
            $session = $this->callService->initiateCall($consumerId, $request->provider_id);
            
            // Broadcast
            broadcast(new CallInitiated($session, [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'profile_photo' => $request->user()->profile_photo,
                'offer' => $request->offer
            ]));

            return ApiResponse::success(['session' => $session], 'Call initiated successfully');
            
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function acceptCall(Request $request, $sessionId)
    {
        $request->validate([
            'answer' => 'required|string'
        ]);

        try {
            $providerId = $request->user()->id;
            $session = $this->callService->acceptCall($sessionId, $providerId);
            
            $session->answer = $request->answer;

            // Broadcast answer
            broadcast(new CallAccepted($session));

            return ApiResponse::success(['session' => $session], 'Call accepted successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function endCall(Request $request, $sessionId)
    {
        try {
            $session = $this->callService->endCall($sessionId, $request->user()->id);
            
            // Broadcast end
            broadcast(new CallEnded($session, $request->user()->id));
            
            return ApiResponse::success(['session' => $session], 'Call ended successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    public function rejectCall(Request $request, $sessionId)
    {
        try {
            $session = $this->callService->endCall($sessionId, $request->user()->id);
            broadcast(new CallEnded($session, $request->user()->id));
            return ApiResponse::success(null, 'Call rejected');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    public function sendIceCandidate(Request $request, $sessionId)
    {
        $request->validate([
            'candidate' => 'required|string'
        ]);

        try {
            $userId = $request->user()->id;
            $session = $this->callService->getSession($sessionId);

            if (!$session || !in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                return ApiResponse::error('Invalid or expired session', 400);
            }

            // Security: Determine receiver and verify participation
            if ($session->consumer_id == $userId) {
                $receiverId = $session->provider_id;
            } elseif ($session->provider_id == $userId) {
                $receiverId = $session->consumer_id;
            } else {
                return ApiResponse::error('Unauthorized participation in this session', 403);
            }

            $candidate = IceCandidate::create([
                'call_session_id' => $sessionId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'candidate' => $request->candidate
            ]);

            broadcast(new IceCandidateSent($session, $request->candidate, $receiverId));

            return ApiResponse::success(null, 'Candidate sent');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
