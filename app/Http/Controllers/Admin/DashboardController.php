<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\LiveSession;
use App\Models\SuperChat;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display comprehensive dynamic admin dashboard.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'today');

        // 1. Resolve Dynamic Date Ranges for Current vs Previous Period Comparison
        [$currentStart, $currentEnd, $previousStart, $previousEnd, $periodLabel] = $this->resolveDateRanges($period);

        // 2. Core Stats & Dynamic Growth Percentages
        $totalUsers = User::where('user_type', 'user')->count();
        $currPeriodUsers = User::where('user_type', 'user')->whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevPeriodUsers = User::where('user_type', 'user')->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $userGrowthPct = $this->calculateGrowthPercentage($currPeriodUsers, $prevPeriodUsers);

        $totalAstrologers = Astrologer::count();
        $approvedAstrologers = Astrologer::where('status', 'approved')->count();
        $pendingAstrologers = Astrologer::where('status', 'pending')->count();
        $onlineAstrologers = Astrologer::where('is_online', true)->count();

        // 3. Dynamic Revenue Calculations (Calls + Chats + SuperChats)
        $currCallRev = (float) CallSession::where('status', 'completed')->whereBetween('created_at', [$currentStart, $currentEnd])->sum('total_cost');
        $prevCallRev = (float) CallSession::where('status', 'completed')->whereBetween('created_at', [$previousStart, $previousEnd])->sum('total_cost');

        $currChatRev = (float) ChatSession::where('status', 'completed')->whereBetween('created_at', [$currentStart, $currentEnd])->sum('total_cost');
        $prevChatRev = (float) ChatSession::where('status', 'completed')->whereBetween('created_at', [$previousStart, $previousEnd])->sum('total_cost');

        $currSuperChatRev = 0;
        $prevSuperChatRev = 0;
        try {
            if (Schema::hasTable('super_chats')) {
                $currSuperChatRev = (float) SuperChat::whereBetween('created_at', [$currentStart, $currentEnd])->sum('amount');
                $prevSuperChatRev = (float) SuperChat::whereBetween('created_at', [$previousStart, $previousEnd])->sum('amount');
            }
        } catch (\Throwable $e) {}

        $periodRevenue = $currCallRev + $currChatRev + $currSuperChatRev;
        $prevRevenue   = $prevCallRev + $prevChatRev + $prevSuperChatRev;
        $revenueGrowthPct = $this->calculateGrowthPercentage($periodRevenue, $prevRevenue);

        // 4. Dynamic Orders Calculations
        $currCallOrders = CallSession::where('status', 'completed')->whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevCallOrders = CallSession::where('status', 'completed')->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $currChatOrders = ChatSession::where('status', 'completed')->whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevChatOrders = ChatSession::where('status', 'completed')->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $periodOrders = $currCallOrders + $currChatOrders;
        $prevOrders   = $prevCallOrders + $prevChatOrders;
        $orderGrowthPct = $this->calculateGrowthPercentage($periodOrders, $prevOrders);

        // 5. Secondary Real-Time Metrics
        $activeSubscriptions = User::whereNotNull('plan_id')
            ->where('plan_expires_at', '>', now())
            ->count();

        $liveCalls = CallSession::where('status', 'ongoing')->count();
        $liveChats = ChatSession::where('status', 'ongoing')->count();
        $liveStreams = 0;
        try {
            if (Schema::hasTable('live_sessions')) {
                $liveStreams = LiveSession::where('status', 'ongoing')->where('is_broadcasting', true)->count();
            }
        } catch (\Throwable $e) {}

        $liveNow = $liveCalls + $liveChats + $liveStreams;

        $pendingPayouts = 0;
        try {
            $pendingPayouts = (float) Wallet::join('users', 'wallets.user_id', '=', 'users.id')
                ->where('users.user_type', 'astrologer')
                ->sum('wallets.balance');
        } catch (\Throwable $e) {}

        $totalWalletBalance = 0;
        try {
            $totalWalletBalance = (float) Wallet::sum('balance');
        } catch (\Throwable $e) {}

        // 6. 7-Day Revenue & Orders Trend for Chart.js
        $chartLabels = [];
        $chartRevenue = [];
        $chartOrders = [];

        for ($i = 6; $i >= 0; $i--) {
            $dayStart = now()->subDays($i)->startOfDay();
            $dayEnd   = now()->subDays($i)->endOfDay();
            $label    = $dayStart->format('d M');

            $dayCallRev = (float) CallSession::where('status', 'completed')->whereBetween('created_at', [$dayStart, $dayEnd])->sum('total_cost');
            $dayChatRev = (float) ChatSession::where('status', 'completed')->whereBetween('created_at', [$dayStart, $dayEnd])->sum('total_cost');
            $daySuperChat = 0;
            try {
                if (Schema::hasTable('super_chats')) {
                    $daySuperChat = (float) SuperChat::whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount');
                }
            } catch (\Throwable $e) {}

            $dayOrders = CallSession::where('status', 'completed')->whereBetween('created_at', [$dayStart, $dayEnd])->count()
                       + ChatSession::where('status', 'completed')->whereBetween('created_at', [$dayStart, $dayEnd])->count();

            $chartLabels[] = $label;
            $chartRevenue[] = round($dayCallRev + $dayChatRev + $daySuperChat, 2);
            $chartOrders[] = $dayOrders;
        }

        // 7. Recent Orders (Combined Calls + Chats + SuperChats)
        $recentOrders = collect();
        try {
            $recentCalls = CallSession::with(['consumer', 'provider'])
                ->where('status', 'completed')
                ->orderByDesc('updated_at')
                ->take(5)
                ->get()
                ->map(fn($s) => [
                    'id'            => 'CALL-' . $s->id,
                    'type'          => 'Call',
                    'icon'          => 'fa-phone-alt',
                    'consumer_name' => $s->consumer->name ?? 'User #' . $s->consumer_id,
                    'provider_name' => $s->provider->name ?? 'Astrologer #' . $s->provider_id,
                    'amount'        => (float) $s->total_cost,
                    'status'        => 'Completed',
                    'created_at'    => $s->ended_at ?? $s->updated_at ?? $s->created_at,
                ]);

            $recentChats = ChatSession::with(['consumer', 'provider'])
                ->where('status', 'completed')
                ->orderByDesc('updated_at')
                ->take(5)
                ->get()
                ->map(fn($s) => [
                    'id'            => 'CHAT-' . $s->id,
                    'type'          => 'Chat',
                    'icon'          => 'fa-comment-dots',
                    'consumer_name' => $s->consumer->name ?? 'User #' . $s->consumer_id,
                    'provider_name' => $s->provider->name ?? 'Astrologer #' . $s->provider_id,
                    'amount'        => (float) $s->total_cost,
                    'status'        => 'Completed',
                    'created_at'    => $s->ended_at ?? $s->updated_at ?? $s->created_at,
                ]);

            $recentOrders = $recentCalls->merge($recentChats)
                ->sortByDesc('created_at')
                ->take(6)
                ->values();
        } catch (\Exception $e) {
            $recentOrders = collect();
        }

        // 8. Top 5 Astrologers by Real Earnings (Strict MySQL & Subquery Safe)
        $topAstrologers = collect();
        try {
            $callEarnings = DB::table('call_sessions')
                ->select('provider_id', DB::raw('COUNT(*) as call_count'), DB::raw('SUM(total_cost) as call_earned'))
                ->where('status', 'completed')
                ->groupBy('provider_id')
                ->get()
                ->keyBy('provider_id');

            $chatEarnings = DB::table('chat_sessions')
                ->select('provider_id', DB::raw('COUNT(*) as chat_count'), DB::raw('SUM(total_cost) as chat_earned'))
                ->where('status', 'completed')
                ->groupBy('provider_id')
                ->get()
                ->keyBy('provider_id');

            $providerIds = $callEarnings->keys()->merge($chatEarnings->keys())->unique()->filter();

            if ($providerIds->isNotEmpty()) {
                $users = User::whereIn('id', $providerIds)->get()->keyBy('id');

                $topAstrologers = $providerIds->map(function ($pid) use ($callEarnings, $chatEarnings, $users) {
                    $user = $users->get($pid);
                    if (!$user) return null;

                    $cCount   = (int) ($callEarnings->get($pid)->call_count ?? 0);
                    $cEarned  = (float) ($callEarnings->get($pid)->call_earned ?? 0);
                    $chCount  = (int) ($chatEarnings->get($pid)->chat_count ?? 0);
                    $chEarned = (float) ($chatEarnings->get($pid)->chat_earned ?? 0);

                    $totalSessions = $cCount + $chCount;
                    $totalEarned   = $cEarned + $chEarned;

                    return (object) [
                        'id'                 => $user->id,
                        'name'               => $user->name ?? 'Astrologer',
                        'profile_photo'      => $user->profile_photo,
                        'total_sessions'     => $totalSessions,
                        'total_earned'       => $totalEarned,
                        'formatted_earnings' => $this->formatCurrency($totalEarned),
                    ];
                })->filter()->sortByDesc('total_earned')->take(5)->values();
            }
        } catch (\Exception $e) {
            $topAstrologers = collect();
        }

        // 9. New Registrations & Expiring Subscriptions
        $newRegistrations = User::where('user_type', 'user')
            ->latest()
            ->take(5)
            ->get();

        $expiringSubscriptions = collect();
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

        $admin = Auth::guard('admin')->user();

        return view('admin.dashboard.index', [
            'period'               => $period,
            'periodLabel'          => $periodLabel,
            'totalUsers'           => $totalUsers,
            'currPeriodUsers'      => $currPeriodUsers,
            'userGrowthPct'        => $userGrowthPct,
            'totalAstrologers'     => $totalAstrologers,
            'approvedAstrologers'  => $approvedAstrologers,
            'pendingAstrologers'   => $pendingAstrologers,
            'onlineAstrologers'    => $onlineAstrologers,
            'periodRevenue'        => $periodRevenue,
            'revenueGrowthPct'     => $revenueGrowthPct,
            'periodOrders'         => $periodOrders,
            'orderGrowthPct'       => $orderGrowthPct,
            'activeSubscriptions'  => $activeSubscriptions,
            'liveNow'              => $liveNow,
            'liveCalls'            => $liveCalls,
            'liveChats'            => $liveChats,
            'liveStreams'          => $liveStreams,
            'pendingPayouts'       => $pendingPayouts,
            'totalWalletBalance'   => $totalWalletBalance,
            'chartLabels'          => $chartLabels,
            'chartRevenue'         => $chartRevenue,
            'chartOrders'          => $chartOrders,
            'recentOrders'         => $recentOrders,
            'topAstrologers'       => $topAstrologers,
            'newRegistrations'     => $newRegistrations,
            'expiringSubscriptions'=> $expiringSubscriptions,
            'admin'                => $admin,
        ]);
    }

    /**
     * Resolve Date Ranges based on selected filter.
     */
    private function resolveDateRanges(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                $now->copy()->subDays(2)->startOfDay(),
                $now->copy()->subDays(2)->endOfDay(),
                'Yesterday',
            ],
            'this_week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
                'This Week',
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                'This Month',
            ],
            'this_year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
                'This Year',
            ],
            'all' => [
                Carbon::createFromTimestamp(0),
                $now->copy()->endOfDay(),
                Carbon::createFromTimestamp(0),
                $now->copy()->endOfDay(),
                'All Time',
            ],
            default => [ // 'today'
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                'Today',
            ],
        };
    }

    /**
     * Calculate Percentage Growth between current and previous metrics.
     */
    private function calculateGrowthPercentage(float|int $current, float|int $previous): array
    {
        if ($previous == 0) {
            $value = $current > 0 ? 100 : 0;
            return [
                'value'     => $value,
                'is_positive' => $value >= 0,
                'formatted' => ($value >= 0 ? '+' : '') . $value . '%',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $rounded = round($change, 1);

        return [
            'value'       => $rounded,
            'is_positive' => $rounded >= 0,
            'formatted'   => ($rounded >= 0 ? '+' : '') . $rounded . '%',
        ];
    }

    /**
     * Smart Indian Currency Formatter (e.g. ₹500, ₹15.2K, ₹1.5L, ₹1.2Cr).
     */
    private function formatCurrency(float $amount): string
    {
        if ($amount >= 10000000) {
            return '₹' . round($amount / 10000000, 2) . 'Cr';
        }
        if ($amount >= 100000) {
            return '₹' . round($amount / 100000, 2) . 'L';
        }
        if ($amount >= 1000) {
            return '₹' . round($amount / 1000, 1) . 'K';
        }
        return '₹' . number_format($amount, 2);
    }
}
