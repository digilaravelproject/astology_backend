<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AstrologerController extends Controller
{
    /**
     * List astrologers with their rates/charges.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Astrologer::with(['user', 'skill', 'otherDetails']);

            // Optionally filter by expertise, language or status
            if ($expertise = $request->query('expertise')) {
                $query->whereJsonContains('areas_of_expertise', $expertise);
            }

            if ($language = $request->query('language')) {
                $query->whereJsonContains('languages', $language);
            }

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            $astrologers = $query->orderBy('id', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologers' => $astrologers,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Astrologer index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch astrologers.'], 500);
        }
    }

    /**
     * Get a single astrologer by ID (including charges).
     */
    public function show($id): JsonResponse
    {
        try {
            $astrologer = Astrologer::with(['user', 'skill', 'otherDetails'])
                ->find($id);

            if (!$astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologer' => $astrologer,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Astrologer show error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch astrologer details.'], 500);
        }
    }
}
