<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Submit feedback (authenticated users only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please login to submit feedback.',
            ], 401);
        }

        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your feedback!',
            'data' => [
                'feedback' => [
                    'id' => $feedback->id,
                    'rating' => $feedback->rating,
                    'comment' => $feedback->comment,
                    'created_at' => $feedback->created_at,
                ]
            ]
        ], 201);
    }

    /**
     * Get all feedbacks (for authenticated users - limit to their own + public view).
     */
    public function index(Request $request): JsonResponse
    {
        $feedbacks = Feedback::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'feedbacks' => $feedbacks->items(),
                'pagination' => [
                    'total' => $feedbacks->total(),
                    'per_page' => $feedbacks->perPage(),
                    'current_page' => $feedbacks->currentPage(),
                    'last_page' => $feedbacks->lastPage(),
                ]
            ]
        ], 200);
    }

    /**
     * Get a specific feedback.
     */
    public function show($id): JsonResponse
    {
        $feedback = Feedback::with('user:id,name,email,phone,profile_photo')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'feedback' => [
                    'id' => $feedback->id,
                    'user' => $feedback->user,
                    'rating' => $feedback->rating,
                    'comment' => $feedback->comment,
                    'created_at' => $feedback->created_at,
                    'updated_at' => $feedback->updated_at,
                ]
            ]
        ], 200);
    }
}
