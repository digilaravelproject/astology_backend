<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoundersWord;
use Illuminate\Http\JsonResponse;

class FoundersWordController extends Controller
{
    /**
     * List active founder messages (latest first).
     */
    public function index(): JsonResponse
    {
        $words = FoundersWord::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($word) {
                return [
                    'id' => $word->id,
                    'title' => $word->title,
                    'message' => $word->message,
                    'image' => $word->image_url,
                    'image_path' => $word->image,
                    'is_active' => $word->is_active,
                    'created_at' => $word->created_at,
                    'updated_at' => $word->updated_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'founders_words' => $words,
            ],
        ], 200);
    }

    /**
     * Get a single founder message.
     */
    public function show($id): JsonResponse
    {
        $word = FoundersWord::where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$word) {
            return response()->json([
                'status' => 'error',
                'message' => 'Founder message not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'founders_word' => [
                    'id' => $word->id,
                    'title' => $word->title,
                    'message' => $word->message,
                    'image' => $word->image_url,
                    'image_path' => $word->image,
                    'is_active' => $word->is_active,
                    'created_at' => $word->created_at,
                    'updated_at' => $word->updated_at,
                ],
            ],
        ], 200);
    }
}
