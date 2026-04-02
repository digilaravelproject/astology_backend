<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remedy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RemedyController extends Controller
{
    /**
     * List all active remedies.
     */
    public function index(): JsonResponse
    {
        try {
            $remedies = Remedy::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'remedies' => $remedies,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Remedy index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch remedies.'], 500);
        }
    }

    /**
     * Get a single remedy by ID.
     */
    public function show($id): JsonResponse
    {
        try {
            $remedy = Remedy::where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$remedy) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Remedy not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'remedy' => $remedy,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Remedy show error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch remedy details.'], 500);
        }
    }
}
