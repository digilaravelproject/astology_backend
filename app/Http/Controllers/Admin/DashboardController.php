<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index()
    {
        // Count total users (regular users)
        $totalUsers = User::where('user_type', 'user')->count();

        // Count total astrologers
        $totalAstrologers = Astrologer::count();

        // Count pending astrologers
        $pendingAstrologers = Astrologer::where('status', 'pending')->count();

        // Count approved astrologers
        $approvedAstrologers = Astrologer::where('status', 'approved')->count();

        // Recent users (last 10)
        $recentUsers = User::where('user_type', 'user')
            ->latest()
            ->take(10)
            ->get();

        // Recent astrologers (last 10)
        $recentAstrologers = Astrologer::with('user')
            ->latest()
            ->take(10)
            ->get();

        $admin = Auth::guard('admin')->user();

        return view('admin.dashboard.index', [
            'totalUsers' => $totalUsers,
            'totalAstrologers' => $totalAstrologers,
            'pendingAstrologers' => $pendingAstrologers,
            'approvedAstrologers' => $approvedAstrologers,
            'recentUsers' => $recentUsers,
            'recentAstrologers' => $recentAstrologers,
            'admin' => $admin,
        ]);
    }
}
