<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PriceIncreaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AstrologerPriceIncreaseController extends Controller
{
    public function __construct(
        private readonly PriceIncreaseService $priceIncreaseService
    ) {}

    public function status(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $data = $this->priceIncreaseService->getStatus($user->astrologer);

            return response()->json([
                'status' => 'success',
                'message' => 'Price increase status retrieved successfully.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('AstrologerPriceIncreaseController::status error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve price increase status.',
            ], 500);
        }
    }

    public function requestIncrease(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $validated = $request->validate([
                'price_type' => 'required|string|in:call,chat',
            ]);

            $priceRequest = $this->priceIncreaseService->requestIncrease(
                $user->astrologer,
                $validated['price_type']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Price increase request submitted successfully.',
                'data' => [
                    'id' => $priceRequest->id,
                    'price_type' => $priceRequest->price_type,
                    'old_price' => (float) $priceRequest->old_price,
                    'new_price' => (float) $priceRequest->new_price,
                    'increase_amount' => (float) $priceRequest->increase_amount,
                    'status' => $priceRequest->status,
                    'created_at' => $priceRequest->created_at->toDateTimeString(),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('AstrologerPriceIncreaseController::requestIncrease error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit price increase request.',
            ], 500);
        }
    }

    public function history(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $data = $this->priceIncreaseService->getHistory($user->astrologer);

            return response()->json([
                'status' => 'success',
                'message' => 'Price increase history retrieved successfully.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('AstrologerPriceIncreaseController::history error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve price increase history.',
            ], 500);
        }
    }
}
