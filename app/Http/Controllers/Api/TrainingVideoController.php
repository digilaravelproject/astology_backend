<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingVideoController extends Controller
{
    /**
     * List training videos, optionally filtered by type.
     */
    public function index(Request $request): JsonResponse
    {
        
        $query = TrainingVideo::where('is_active', true);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $videos = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'videos' => $videos,
            ],
        ], 200);
    }

    /**
     * Get a single training video by id.
     */
    public function show($id): JsonResponse
    {
        $video = TrainingVideo::where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$video) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'video' => $video,
            ],
        ], 200);
    }
}
