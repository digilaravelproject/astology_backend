<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AstrologerCommunityController extends Controller
{
    public function index(Request $request)
    {
        $query = AstrologerCommunity::with(['astrologer.user', 'user']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('astrologer.user', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('astrologer_id')) {
            $query->where('astrologer_id', $request->input('astrologer_id'));
        }

        $communityRecords = $query->latest()->paginate(20)->withQueryString();
        $astrologers = Astrologer::with('user')->orderBy('id')->get();

        $stats = [
            'total' => AstrologerCommunity::count(),
            'liked' => AstrologerCommunity::where('is_liked', true)->count(),
            'blocked' => AstrologerCommunity::where('is_blocked', true)->count(),
            'reported' => AstrologerCommunity::whereNotNull('report_reason')->where('report_reason', '<>', '')->count(),
        ];

        return view('admin.astrologers.community', compact('communityRecords', 'astrologers', 'stats'));
    }

    public function toggleLike(AstrologerCommunity $community)
    {
        $community->is_liked = !$community->is_liked;
        $community->liked_at = $community->is_liked ? Carbon::now() : null;
        $community->save();

        return back()->with('success', $community->is_liked ? 'Member marked as liked.' : 'Member like removed.');
    }

    public function toggleBlock(AstrologerCommunity $community)
    {
        $community->is_blocked = !$community->is_blocked;
        $community->blocked_at = $community->is_blocked ? Carbon::now() : null;
        $community->save();

        return back()->with('success', $community->is_blocked ? 'Member blocked successfully.' : 'Member unblocked successfully.');
    }

    public function destroy(AstrologerCommunity $community)
    {
        $community->delete();

        return back()->with('success', 'Community member record removed successfully.');
    }

    public function reported(Request $request)
    {
        $query = AstrologerCommunity::with(['astrologer.user', 'user'])
            ->whereNotNull('report_reason')
            ->where('report_reason', '<>', '');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('astrologer.user', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhere('report_reason', 'like', "%{$search}%");
            });
        }

        if ($request->filled('astrologer_id')) {
            $query->where('astrologer_id', $request->input('astrologer_id'));
        }

        $reports = $query->latest()->paginate(20)->withQueryString();
        $astrologers = Astrologer::with('user')->orderBy('id')->get();

        $stats = [
            'total' => AstrologerCommunity::whereNotNull('report_reason')->where('report_reason', '<>', '')->count(),
            'blocked' => AstrologerCommunity::where('is_blocked', true)->count(),
            'resolved' => AstrologerCommunity::whereNull('report_reason')->orWhere('report_reason', '')->count(),
        ];

        return view('admin.astrologers.reported', compact('reports', 'astrologers', 'stats'));
    }

    public function resolveReport(AstrologerCommunity $community)
    {
        $community->report_reason = null;
        $community->reported_at = null;
        $community->save();

        return back()->with('success', 'Report has been marked as resolved.');
    }
}
