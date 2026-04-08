<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerReview;
use Illuminate\Http\Request;

class AstrologerReviewController extends Controller
{
    public function index()
    {
        $platform = AstrologerReview::query()
            ->selectRaw('count(*) as total_reviews')
            ->selectRaw('coalesce(avg(rating), 0) as average_rating')
            ->selectRaw('sum(case when rating <= 2 then 1 else 0 end) as critical_reviews')
            ->selectRaw('sum(case when reply IS NOT NULL and reply <> "" then 1 else 0 end) as replied_reviews')
            ->first();

        $astrologers = Astrologer::with(['user'])
            ->withCount(['reviews', 'reviews as critical_reviews_count' => function ($query) {
                $query->where('rating', '<=', 2);
            }])
            ->withAvg('reviews', 'rating')
            ->get();

        $recentReviews = AstrologerReview::with(['astrologer.user', 'user'])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.astrologers.reviews', compact('platform', 'astrologers', 'recentReviews'));
    }

    public function reply(Request $request, AstrologerReview $review)
    {
        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $review->update([
            'reply' => $request->input('reply'),
            'reply_at' => now(),
        ]);

        return back()->with('success', 'Reply saved successfully.');
    }

    public function destroy(AstrologerReview $review)
    {
        $review->delete();

        return back()->with('success', 'Review deleted successfully.');
    }
}
