<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AstrologerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AstrologerController extends Controller
{
    public function __construct(
        private readonly AstrologerService $astrologerService
    ) {}

    /**
     * List astrologers with their rates/charges.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type', 'min_price', 'max_price', 'skills', 'language', 'min_rating', 'is_online', 'sort_by', 'search_query'
            ]);
            $currentUser = Auth::guard('sanctum')->user();

            $data = $this->astrologerService->listAstrologers($filters, $currentUser);

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            Log::error('Astrologer index error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch astrologers.',
            ], 500);
        }
    }

    /**
     * Get a single astrologer by ID (including charges).
     */
    public function show($id): JsonResponse
    {
        try {
            $currentUser = Auth::guard('sanctum')->user();
            $astrologer = $this->astrologerService->getAstrologerDetails((int) $id, $currentUser);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologer' => $astrologer,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Astrologer show error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch astrologer details.',
            ], 500);
        }
    }

    /**
     * Get unified order history / waiting list for the logged-in astrologer.
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            $filters = [
                'status' => $request->query('status'),
                'type' => $request->query('type'),
                'per_page' => $request->query('per_page', 15),
                'page' => $request->query('page', 1),
            ];

            $data = $this->astrologerService->getAstrologerOrders($user, $filters);

            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully.',
                'data' => $data,
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 403);
        } catch (\Exception $e) {
            Log::error('Astrologer getOrders error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch order history.',
            ], 500);
        }
    }

    /**
     * Get dynamic performance metrics for the authenticated astrologer.
     */
    public function getPerformance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.',
                ], 401);
            }

            $data = $this->astrologerService->getPerformanceMetrics($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Astrologer performance data retrieved successfully.',
                'data' => $data,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Astrologer getPerformance error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch performance data.',
            ], 500);
        }
    }
}
