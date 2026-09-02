<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchasePackageRequest;
use App\Http\Requests\StartPackageSubSessionRequest;
use App\Http\Requests\EndPackageSubSessionRequest;
use App\Services\PackageService;
use App\Services\SessionTimerService;
use App\Models\PackagePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class PackageSessionController extends Controller
{
    protected $packageService;
    protected $timerService;

    public function __construct(PackageService $packageService, SessionTimerService $timerService)
    {
        $this->packageService = $packageService;
        $this->timerService = $timerService;
    }

    /**
     * Purchase a package for an astrologer.
     */
    public function purchase(PurchasePackageRequest $request)
    {
        $trackingUuid = (string) Str::uuid();
        try {
            $userId = $request->user()->id;
            $purchase = $this->packageService->purchasePackage($userId, $request->astrologer_id);

            return response()->json([
                'success' => true,
                'message' => 'Package purchased successfully.',
                'data' => [
                    'purchase' => $purchase
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error("Package purchase failed. Tracking UUID: {$trackingUuid}. Error: " . $e->getMessage());

            $errorCode = $e->getCode() === 422 ? 'INSUFFICIENT_BALANCE' : 'PACKAGE_PURCHASE_FAILED';

            return response()->json([
                'success' => false,
                'error_code' => $errorCode,
                'message' => $e->getMessage() ?: 'Transaction aborted due to database resource constraint.',
                'tracking_uuid' => $trackingUuid
            ], 422);
        }
    }

    /**
     * Get active package status for a specific astrologer.
     */
    public function activeStatus(Request $request)
    {
        $trackingUuid = (string) Str::uuid();
        try {
            $request->validate([
                'astrologer_id' => 'required|exists:users,id'
            ]);

            $userId = $request->user()->id;
            $purchase = PackagePurchase::where('user_id', $userId)
                ->where('astrologer_id', $request->astrologer_id)
                ->where('status', 'active')
                ->where('remaining_duration', '>', 0)
                ->first();

            $activeSubSession = $purchase 
                ? $this->timerService->getActiveSubSession($userId)
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'has_active_package' => !is_null($purchase),
                    'package_purchase' => $purchase,
                    'active_sub_session' => $activeSubSession
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Fetching active package status failed. Tracking UUID: {$trackingUuid}. Error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => 'ACTIVE_STATUS_FAILED',
                'message' => $e->getMessage() ?: 'Unable to fetch package status.',
                'tracking_uuid' => $trackingUuid
            ], 400);
        }
    }

    /**
     * Start a package sub-session (chat/call).
     */
    public function startSession(StartPackageSubSessionRequest $request)
    {
        $trackingUuid = (string) Str::uuid();
        try {
            $userId   = $request->user()->id;
            $question = $request->input('question'); // optional for chat

            $result = $this->timerService->startSubSession(
                $userId,
                $request->astrologer_id,
                $request->mode,
                $question,
                $request->input('offer')
            );

            $subSession = $result['sub_session'];

            $responseData = [
                'sub_session'        => $subSession,
                'remaining_duration' => $subSession->purchase->remaining_duration ?? null,
            ];

            // Include the actual linked chat or call session so Flutter can wire up the UI
            if (isset($result['chat_session'])) {
                $responseData['chat_session'] = $result['chat_session'];
            }
            if (isset($result['call_session'])) {
                $responseData['call_session'] = $result['call_session'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Package sub-session started successfully.',
                'data'    => $responseData,
            ], 200);

        } catch (Exception $e) {
            Log::error("Starting package sub-session failed. Tracking UUID: {$trackingUuid}. Error: " . $e->getMessage());

            return response()->json([
                'success'       => false,
                'error_code'    => 'PACKAGE_SESSION_START_FAILED',
                'message'       => $e->getMessage() ?: 'Could not initiate sub-session.',
                'tracking_uuid' => $trackingUuid
            ], 422);
        }
    }

    /**
     * End a package sub-session.
     */
    public function endSession(EndPackageSubSessionRequest $request)
    {
        $trackingUuid = (string) Str::uuid();
        try {
            $userId = $request->user()->id;
            $subSession = $this->timerService->endSubSession($request->sub_session_id, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Package sub-session ended successfully.',
                'data' => [
                    'sub_session' => $subSession,
                    'remaining_duration' => $subSession->purchase->remaining_duration
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error("Ending package sub-session failed. Tracking UUID: {$trackingUuid}. Error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => 'PACKAGE_SESSION_END_FAILED',
                'message' => $e->getMessage() ?: 'Could not terminate sub-session.',
                'tracking_uuid' => $trackingUuid
            ], 422);
        }
    }

    /**
     * Spawn an additional channel (Call or Chat) within active session.
     */
    public function spawnChannel(Request $request)
    {
        $request->validate([
            'sub_session_id' => 'required|integer|exists:package_sub_sessions,id',
            'channel_type'   => 'required|in:call,chat',
            'call_type'      => 'nullable|in:audio,video',
            'question'       => 'nullable|string',
        ]);

        try {
            $engine = app(\App\Services\PackageSessionEngineService::class);
            $result = $engine->spawnSubchannel(
                $request->sub_session_id,
                $request->channel_type,
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'success'         => true,
                'message'         => ucfirst($request->channel_type) . ' channel spawned successfully.',
                'chat_session_id' => $result['chat_session_id'] ?? null,
                'call_session_id' => $result['call_session_id'] ?? null,
                'chat_status'     => $result['chat_status'] ?? null,
                'call_status'     => $result['call_status'] ?? null,
                'data'            => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Atomically switch channel (Chat <-> Call) within active package session.
     */
    public function switchChannel(Request $request)
    {
        $request->validate([
            'sub_session_id' => 'required|integer|exists:package_sub_sessions,id',
            'from_channel'   => 'required|in:call,chat',
            'to_channel'     => 'required|in:call,chat|different:from_channel',
            'offer'          => 'nullable|string',
            'question'       => 'nullable|string',
        ]);

        try {
            $engine = app(\App\Services\PackageSessionEngineService::class);
            $result = $engine->switchChannel(
                $request->sub_session_id,
                $request->from_channel,
                $request->to_channel,
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Switched to ' . ucfirst($request->to_channel) . ' successfully.',
                'data'    => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Terminate a specific subchannel with modal options (End Call Only vs End Complete Session).
     */
    public function terminateChannel(Request $request)
    {
        $request->validate([
            'sub_session_id' => 'required|integer|exists:package_sub_sessions,id',
            'channel_type'   => 'required|in:call,chat',
            'action'         => 'required|in:end_channel_only,end_complete_session,channel_only,complete_session',
        ]);

        try {
            $engine = app(\App\Services\PackageSessionEngineService::class);
            $result = $engine->terminateSubchannel(
                $request->sub_session_id,
                $request->channel_type,
                $request->action,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Channel terminated successfully.',
                'data'    => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Client Heartbeat Ping to prevent accidental timeout.
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'sub_session_id' => 'required|integer|exists:package_sub_sessions,id',
        ]);

        try {
            $engine = app(\App\Services\PackageSessionEngineService::class);
            $result = $engine->recordHeartbeat($request->sub_session_id, $request->user()->id);

            return response()->json([
                'success' => true,
                'data'    => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get overarching floating banner context for active package session.
     */
    public function activeBanner(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $subSession = \App\Models\PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])
                ->whereIn('session_state', ['in_progress', 'paused'])
                ->whereHas('purchase', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhere('astrologer_id', $userId);
                })
                ->latest('id')
                ->first();

            if (!$subSession) {
                return response()->json([
                    'success' => true,
                    'data'    => null,
                ], 200);
            }

            $engine = app(\App\Services\PackageSessionEngineService::class);
            $remaining = $engine->getRemainingSeconds($subSession);
            $bannerData = $subSession->toBannerArray($remaining);

            return response()->json([
                'success' => true,
                'data'    => $bannerData,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
