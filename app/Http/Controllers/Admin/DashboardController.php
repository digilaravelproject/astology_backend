<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\User;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index()
    {
        $today = now()->startOfDay();

        // ===== CORE STATS =====
        // Count total users (regular users)
        $totalUsers = User::where('user_type', 'user')->count();

        // Count total astrologers
        $totalAstrologers = Astrologer::count();

        // Today's revenue (sum of completed call and chat sessions)
        try {
            $todayRevenue = CallSession::where('status', 'completed')
                ->whereDate('created_at', $today)
                ->sum('total_cost')
                +
                ChatSession::where('status', 'completed')
                    ->whereDate('created_at', $today)
                    ->sum('total_cost');
        } catch (\Exception $e) {
            $todayRevenue = 0;
        }

        // Today's orders (count of completed call and chat sessions)
        try {
            $todayOrders = CallSession::where('status', 'completed')
                ->whereDate('created_at', $today)
                ->count()
                +
                ChatSession::where('status', 'completed')
                    ->whereDate('created_at', $today)
                    ->count();
        } catch (\Exception $e) {
            $todayOrders = 0;
        }

        // ===== SECONDARY STATS =====
        // Count active subscriptions (users with active plan)
        $activeSubscriptions = User::whereNotNull('plan_id')
            ->where('plan_expires_at', '>', now())
            ->count();

        // Count live astrologers (ongoing call or chat sessions)
        try {
            $liveNow = CallSession::where('status', 'ongoing')->count() 
                      + ChatSession::where('status', 'ongoing')->count();
        } catch (\Exception $e) {
            $liveNow = 0;
        }

        // Pending payouts (sum of astrologer wallet balances)
        try {
            $pendingPayouts = Wallet::where('user_type', 'astrologer')
                ->sum('balance');
        } catch (\Exception $e) {
            $pendingPayouts = 0;
        }

        // Total wallet balance (all user wallets)
        try {
            $totalWalletBalance = Wallet::sum('balance');
        } catch (\Exception $e) {
            $totalWalletBalance = 0;
        }

        // Count pending astrologers
        $pendingAstrologers = Astrologer::where('status', 'pending')->count();

        // Count approved astrologers
        $approvedAstrologers = Astrologer::where('status', 'approved')->count();

        // ===== RECENT ORDERS (last 5 completed sessions) =====
        $recentOrders = collect();
        
        try {
            // Get recent completed calls
            $recentCalls = CallSession::with(['consumer', 'provider'])
                ->where('status', 'completed')
                ->orderByDesc('updated_at')
                ->take(5)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CALL-' . $session->id,
                        'type' => 'call',
                        'consumer_name' => $session->consumer->name ?? 'N/A',
                        'provider_name' => $session->provider->name ?? 'N/A',
                        'amount' => $session->total_cost,
                        'status' => 'Success',
                        'created_at' => $session->completed_at ?? $session->updated_at,
                    ];
                });

            // Get recent completed chats
            $recentChats = ChatSession::with(['consumer', 'provider'])
                ->where('status', 'completed')
                ->orderByDesc('updated_at')
                ->take(5)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CHAT-' . $session->id,
                        'type' => 'chat',
                        'consumer_name' => $session->consumer->name ?? 'N/A',
                        'provider_name' => $session->provider->name ?? 'N/A',
                        'amount' => $session->total_cost,
                        'status' => 'Success',
                        'created_at' => $session->completed_at ?? $session->updated_at,
                    ];
                });

            // Merge and sort by created_at, take top 5
            $recentOrders = $recentCalls->merge($recentChats)
                ->sortByDesc('created_at')
                ->take(5)
                ->values();
        } catch (\Exception $e) {
            $recentOrders = collect();
        }

        // ===== TOP 5 ASTROLOGERS =====
        $topAstrologers = collect();
        
        try {
            $topAstrologers = User::select('users.id', 'users.name')
                ->selectRaw('COUNT(CASE WHEN call_sessions.status = "completed" THEN 1 END) + COUNT(CASE WHEN chat_sessions.status = "completed" THEN 1 END) as total_sessions')
                ->selectRaw('COALESCE(SUM(CASE WHEN call_sessions.status = "completed" THEN call_sessions.total_cost ELSE 0 END), 0) + COALESCE(SUM(CASE WHEN chat_sessions.status = "completed" THEN chat_sessions.total_cost ELSE 0 END), 0) as total_earned')
                ->leftJoin('call_sessions', function ($join) {
                    $join->on('users.id', '=', 'call_sessions.provider_id');
                })
                ->leftJoin('chat_sessions', function ($join) {
                    $join->on('users.id', '=', 'chat_sessions.provider_id');
                })
                ->where('users.user_type', 'astrologer')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_earned')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $topAstrologers = collect();
        }

        // ===== NEW REGISTRATIONS (last 5) =====
        $newRegistrations = User::where('user_type', 'user')
            ->latest()
            ->take(5)
            ->get();

        // ===== EXPIRING SUBSCRIPTIONS (expiring in next 7 days) =====
        try {
            $expiringSubscriptions = User::with('plan')
                ->whereNotNull('plan_id')
                ->whereBetween('plan_expires_at', [now(), now()->addDays(7)])
                ->orderBy('plan_expires_at')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $expiringSubscriptions = collect();
        }

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
            // Core stats
            'totalUsers' => $totalUsers,
            'totalAstrologers' => $totalAstrologers,
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrders,
            
            // Secondary stats
            'activeSubscriptions' => $activeSubscriptions,
            'liveNow' => $liveNow,
            'pendingPayouts' => $pendingPayouts,
            'totalWalletBalance' => $totalWalletBalance,
            
            // Other data
            'pendingAstrologers' => $pendingAstrologers,
            'approvedAstrologers' => $approvedAstrologers,
            'recentOrders' => $recentOrders,
            'topAstrologers' => $topAstrologers,
            'newRegistrations' => $newRegistrations,
            'expiringSubscriptions' => $expiringSubscriptions,
            'recentUsers' => $recentUsers,
            'recentAstrologers' => $recentAstrologers,
            'admin' => $admin,
        ]);
    }
}
