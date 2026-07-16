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
                $question
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
}
