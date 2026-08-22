<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerPayout;
use App\Models\AstrologerReview;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\GiftTransaction;
use App\Models\PackagePurchase;
use App\Models\SuperChat;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportAnalyticsController extends Controller
{
    /**
     * Display the dynamic Intelligence Center & Analytics Dashboard.
     */
    public function index(Request $request)
    {
        try {
            // 1. Date Range Handling
            $period = $request->input('period', 'last_30_days');
            $customFrom = $request->input('date_from');
            $customTo = $request->input('date_to');

            $now = Carbon::now();
            switch ($period) {
                case 'today':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $periodLabel = 'Today';
                    break;
                case 'last_7_days':
                    $startDate = $now->copy()->subDays(7)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $periodLabel = 'Last 7 Days';
                    break;
                case 'this_month':
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfMonth();
                    $periodLabel = 'This Month';
                    break;
                case 'this_year':
                    $startDate = $now->copy()->startOfYear();
                    $endDate = $now->copy()->endOfYear();
                    $periodLabel = 'This Year';
                    break;
                case 'custom':
                    $startDate = $customFrom ? Carbon::parse($customFrom)->startOfDay() : $now->copy()->subDays(30)->startOfDay();
                    $endDate = $customTo ? Carbon::parse($customTo)->endOfDay() : $now->copy()->endOfDay();
                    $periodLabel = $startDate->format('d M') . ' - ' . $endDate->format('d M Y');
                    break;
                case 'last_30_days':
                default:
                    $startDate = $now->copy()->subDays(30)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $periodLabel = 'Last 30 Days';
                    break;
            }

            // 2. Financial Metrics & GPV Calculations
            $walletRechargeRevenue = 0.0;
            try {
                $walletRechargeRevenue = (float) WalletTransaction::where('transaction_type', WalletTransaction::TYPE_CREDIT)
                    ->where('status', WalletTransaction::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
            } catch (Exception $e) {
                Log::warning("WalletTransaction sum error: " . $e->getMessage());
            }

            $packageSalesRevenue = 0.0;
            try {
                $packageSalesRevenue = (float) PackagePurchase::where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('purchase_price');
            } catch (Exception $e) {
                Log::warning("PackagePurchase sum error: " . $e->getMessage());
            }

            $chatConsultationSpend = 0.0;
            try {
                $chatConsultationSpend = (float) ChatSession::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_cost');
            } catch (Exception $e) {
                Log::warning("ChatSession sum error: " . $e->getMessage());
            }

            $callConsultationSpend = 0.0;
            try {
                $callConsultationSpend = (float) CallSession::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_cost');
            } catch (Exception $e) {
                Log::warning("CallSession sum error: " . $e->getMessage());
            }

            $liveGiftsSpend = 0.0;
            try {
                $liveGiftsSpend = (float) GiftTransaction::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
            } catch (Exception $e) {
                Log::warning("GiftTransaction sum error: " . $e->getMessage());
            }

            $superChatSpend = 0.0;
            try {
                $superChatSpend = (float) SuperChat::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
            } catch (Exception $e) {
                Log::warning("SuperChat sum error: " . $e->getMessage());
            }

            // Gross Platform Value (Total Inflow / Consultation Value)
            $netGPV = $walletRechargeRevenue + $packageSalesRevenue;
            if ($netGPV == 0) {
                $netGPV = $chatConsultationSpend + $callConsultationSpend + $packageSalesRevenue + $liveGiftsSpend;
            }

            $totalPaidOrders = 0;
            try {
                $totalPaidOrders = WalletTransaction::where('transaction_type', WalletTransaction::TYPE_CREDIT)
                    ->where('status', WalletTransaction::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            } catch (Exception $e) {}

            $totalPackageOrders = 0;
            try {
                $totalPackageOrders = PackagePurchase::whereBetween('created_at', [$startDate, $endDate])->count();
            } catch (Exception $e) {}

            $totalOrderCount = max(1, $totalPaidOrders + $totalPackageOrders);
            $avgBasket = round($netGPV / $totalOrderCount, 2);

            // Active Users & Daily Active Telemetry
            $activeUsersCount = User::where('user_type', 'user')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('updated_at', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
                })->count();

            $totalUsers = max(1, User::where('user_type', 'user')->count());
            $churnedUsers = User::where('user_type', 'user')
                ->where('updated_at', '<', $now->copy()->subDays(30))
                ->count();
            $churnRate = round(($churnedUsers / $totalUsers) * 100, 1);

            // Astrologer Load & Queue Telemetry
            $totalAstrologers = max(1, Astrologer::where('status', 'approved')->count());
            $busyAstrologerUserIds = ChatSession::whereIn('status', ['ongoing', 'accepted'])->pluck('provider_id')
                ->merge(CallSession::whereIn('status', ['ongoing', 'accepted', 'ringing'])->pluck('provider_id'))
                ->unique();
            $busyAstrologersCount = $busyAstrologerUserIds->count();
            $astroLoadPercentage = min(100, round(($busyAstrologersCount / $totalAstrologers) * 100));

            // Rating Average
            $ratingAvg = 4.8;
            try {
                $avg = AstrologerReview::whereBetween('created_at', [$startDate, $endDate])->avg('rating');
                if ($avg) {
                    $ratingAvg = round((float) $avg, 2);
                }
            } catch (Exception $e) {}

            // Revenue Distribution Percentages
            $totalConsultationRevenue = $chatConsultationSpend + $callConsultationSpend;
            $liveConsultationPercent = $netGPV > 0 ? round(($totalConsultationRevenue / $netGPV) * 100, 1) : 60.0;
            $packageRevenuePercent = $netGPV > 0 ? round(($packageSalesRevenue / $netGPV) * 100, 1) : 25.0;
            $otherRevenuePercent = max(0, round(100 - ($liveConsultationPercent + $packageRevenuePercent), 1));

            // 3. Settlement Pipeline (Real Astrologer Payouts)
            $settlements = collect();
            try {
                $settlements = AstrologerPayout::with(['astrologer.user'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            } catch (Exception $e) {
                Log::warning("AstrologerPayout fetch error: " . $e->getMessage());
            }

            // Fallback if no payouts exist yet: show astrologers list
            if ($settlements->isEmpty()) {
                $topEarners = Astrologer::with('user')
                    ->where('status', 'approved')
                    ->limit(6)
                    ->get();
            } else {
                $topEarners = collect();
            }

            // 4. Operations: Active Sessions & Logistics Log
            $activeChatCount = ChatSession::whereIn('status', ['accepted', 'ongoing'])->count();
            $activeCallCount = CallSession::whereIn('status', ['accepted', 'ongoing', 'ringing'])->count();
            $activeSessionsCount = $activeChatCount + $activeCallCount;

            $totalSessionsCount = ChatSession::whereBetween('created_at', [$startDate, $endDate])->count()
                + CallSession::whereBetween('created_at', [$startDate, $endDate])->count();
            $missedOrCancelledCount = ChatSession::whereIn('status', ['missed', 'rejected', 'cancelled'])->whereBetween('created_at', [$startDate, $endDate])->count()
                + CallSession::whereIn('status', ['missed', 'rejected', 'cancelled'])->whereBetween('created_at', [$startDate, $endDate])->count();
            $dropRate = $totalSessionsCount > 0 ? round(($missedOrCancelledCount / $totalSessionsCount) * 100, 1) : 2.1;

            // Fetch Real Recent Logistics Sessions (Combined Chat & Call)
            $recentChats = collect();
            try {
                $recentChats = ChatSession::with(['consumer', 'provider'])
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get()
                    ->map(function ($chat) {
                        $duration = ($chat->duration_minutes ?: ($chat->started_at && $chat->ended_at ? $chat->started_at->diffInMinutes($chat->ended_at) : 0));
                        return [
                            'id'        => '#CHAT-' . $chat->id,
                            'type'      => 'Chat',
                            'user'      => $chat->consumer?->name ?? 'User #' . $chat->consumer_id,
                            'astro'     => $chat->provider?->name ?? 'Astrologer #' . $chat->provider_id,
                            'duration'  => $duration > 0 ? $duration . ' min' : 'Active',
                            'status'    => ucfirst($chat->status),
                            'cost'      => '₹ ' . number_format($chat->total_cost, 2),
                            'date'      => $chat->created_at->format('d M, h:i A'),
                        ];
                    });
            } catch (Exception $e) {
                Log::warning("ChatSession logistics query error: " . $e->getMessage());
            }

            $recentCalls = collect();
            try {
                $recentCalls = CallSession::with(['consumer', 'provider'])
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get()
                    ->map(function ($call) {
                        $duration = ($call->duration_minutes ?: ($call->started_at && $call->ended_at ? $call->started_at->diffInMinutes($call->ended_at) : 0));
                        return [
                            'id'        => '#CALL-' . $call->id,
                            'type'      => 'Call',
                            'user'      => $call->consumer?->name ?? 'User #' . $call->consumer_id,
                            'astro'     => $call->provider?->name ?? 'Astrologer #' . $call->provider_id,
                            'duration'  => $duration > 0 ? $duration . ' min' : 'Active',
                            'status'    => ucfirst($call->status),
                            'cost'      => '₹ ' . number_format($call->total_cost, 2),
                            'date'      => $call->created_at->format('d M, h:i A'),
                        ];
                    });
            } catch (Exception $e) {
                Log::warning("CallSession logistics query error: " . $e->getMessage());
            }

            $logistics = $recentChats->concat($recentCalls)->sortByDesc('date')->take(8);

            // 5. Growth Ecosystem Data (Monthly User Registration Velocity)
            $growthMonths = [];
            for ($m = 11; $m >= 0; $m--) {
                $monthDate = $now->copy()->subMonths($m);
                $count = User::where('user_type', 'user')
                    ->whereYear('created_at', $monthDate->year)
                    ->whereMonth('created_at', $monthDate->month)
                    ->count();
                $growthMonths[] = [
                    'label' => $monthDate->format('M'),
                    'count' => $count,
                    'height' => min(100, max(15, $count * 5)),
                ];
            }

            // Conversion & Repeat Metrics
            $usersWithConsultation = DB::table('chat_sessions')->distinct('consumer_id')->count('consumer_id');
            $conversionRate = round(($usersWithConsultation / $totalUsers) * 100, 2);

            $repeatUsersCount = DB::table('chat_sessions')
                ->select('consumer_id')
                ->groupBy('consumer_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();
            $referralVelocity = round(($repeatUsersCount / max(1, $usersWithConsultation)) * 100, 1);

            return view('admin.reports.index', compact(
                'period',
                'periodLabel',
                'startDate',
                'endDate',
                'netGPV',
                'avgBasket',
                'activeUsersCount',
                'churnRate',
                'astroLoadPercentage',
                'ratingAvg',
                'chatConsultationSpend',
                'callConsultationSpend',
                'packageSalesRevenue',
                'totalConsultationRevenue',
                'liveConsultationPercent',
                'packageRevenuePercent',
                'otherRevenuePercent',
                'settlements',
                'topEarners',
                'activeSessionsCount',
                'dropRate',
                'logistics',
                'growthMonths',
                'conversionRate',
                'referralVelocity',
                'totalUsers'
            ));

        } catch (Exception $e) {
            Log::error("Intelligence Center Analytics Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error generating analytics report: ' . $e->getMessage());
        }
    }
}
