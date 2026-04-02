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
            $session = $this->callService->endCall($sessionId);
            
            // Broadcast end
            broadcast(new CallEnded($session, $request->user()->id));
            
            return ApiResponse::success(['session' => $session], 'Call ended successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function rejectCall(Request $request, $sessionId)
    {
        try {
            $session = $this->callService->endCall($sessionId);
            // You might want a specific CallRejected event, but CallEnded with ended_by is often enough
            broadcast(new CallEnded($session, $request->user()->id));
            return ApiResponse::success(null, 'Call rejected');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function sendIceCandidate(Request $request, $sessionId)
    {
        $request->validate([
            'candidate' => 'required|string',
            'receiver_id' => 'required|exists:users,id'
        ]);

        $candidate = IceCandidate::create([
            'call_session_id' => $sessionId,
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'candidate' => $request->candidate
        ]);

        broadcast(new IceCandidateSent($session = null, $request->candidate, $request->receiver_id));

        return ApiResponse::success(null, 'Candidate sent');
    }
}
