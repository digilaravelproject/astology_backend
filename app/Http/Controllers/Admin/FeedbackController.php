<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display all feedbacks with filters.
     */
    public function index(Request $request)
    {
        $query = Feedback::with('user:id,name,email,phone,profile_photo');

        // Search by user name or email or feedback comment
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('comment', 'like', "%{$search}%");
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->input('rating'));
        }

        $feedbacks = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $stats = [
            'total' => Feedback::count(),
            'with_rating' => Feedback::whereNotNull('rating')->count(),
            '5_star' => Feedback::where('rating', 5)->count(),
            '4_star' => Feedback::where('rating', 4)->count(),
            '3_star' => Feedback::where('rating', 3)->count(),
            '2_star' => Feedback::where('rating', 2)->count(),
            '1_star' => Feedback::where('rating', 1)->count(),
            'avg_rating' => number_format(Feedback::whereNotNull('rating')->avg('rating') ?? 0, 1),
        ];

        return view('admin.feedbacks.index', compact('feedbacks', 'stats'));
    }

    /**
     * View a specific feedback.
     */
    public function show($id)
    {
        $feedback = Feedback::with('user:id,name,email,phone,profile_photo')->findOrFail($id);
        return view('admin.feedbacks.show', compact('feedback'));
    }

    /**
     * Delete a feedback.
     */
    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return redirect()->route('admin.feedbacks.index')->with('success', 'Feedback deleted successfully.');
    }
}
