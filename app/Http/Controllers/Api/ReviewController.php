<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AstrologerReview;
use App\Models\Astrologer;
use App\Models\User;
use App\Services\NotificationHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a new astrologer review by authenticated user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'astrologer_id' => 'required|exists:astrologers,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $review = AstrologerReview::create([
            'astrologer_id' => $validated['astrologer_id'],
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
        ]);

        // Notify astrologer about new review
        $astrologer = Astrologer::with('user')->find($validated['astrologer_id']);
        if ($astrologer && $astrologer->user) {
            NotificationHelper::send(
                $astrologer->user->id,
                'New review received',
                "{$user->name} rated you {$validated['rating']}/5 and left a review.",
                ['review_id' => $review->id, 'astrologer_id' => $astrologer->id, 'user_id' => $user->id]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Review posted successfully.',
            'data' => ['review' => $review],
        ], 201);
    }

    /**
     * Astrologer replies to a review.
     */
    public function reply(Request $request, $reviewId)
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'astrologer' || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only astrologers can reply to reviews.',
            ], 403);
        }

        $validated = $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        $review = AstrologerReview::find($reviewId);
        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found.',
            ], 404);
        }

        if ($review->astrologer_id !== $user->astrologer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only reply to reviews for your own profile.',
            ], 403);
        }

        $review->reply = $validated['reply'];
        $review->reply_at = Carbon::now();
        $review->save();

        // Notify user who created the review.
        $reviewOwner = $review->user;
        if ($reviewOwner) {
            NotificationHelper::send(
                $reviewOwner->id,
                'Astrologer replied to your review',
                "Your review got a reply: {$validated['reply']}",
                ['review_id' => $review->id, 'astrologer_id' => $review->astrologer_id]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reply saved successfully.',
            'data' => ['review' => $review],
        ], 200);
    }

    /**
     * Get reviews by astrologer_id or user_id query parameter.
     */
    public function index(Request $request)
    {
        $astrologerId = $request->query('astrologer_id');
        $userId = $request->query('user_id');

        if (!$astrologerId && !$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide astrologer_id or user_id in query.',
            ], 400);
        }

        if ($astrologerId) {
            $astrologer = Astrologer::find($astrologerId);
            if (!$astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer not found.',
                ], 404);
            }

            $reviews = AstrologerReview::with('user')
                ->where('astrologer_id', $astrologerId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologer' => $astrologer,
                    'reviews' => $reviews,
                ],
            ], 200);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $reviews = AstrologerReview::with('astrologer.user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'reviews' => $reviews,
            ],
        ], 200);
    }
}
