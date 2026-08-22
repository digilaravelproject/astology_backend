<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AstrologerPerformanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $timeRange = $request->query('range', 'all'); // 'today', 'week', 'month', 'all'
            $search = $request->query('search');

            $dateFilter = match ($timeRange) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                default => null,
            };

            // Query astrologers with user relation
            $astrologersQuery = Astrologer::with(['user', 'level'])
                ->where('status', 'approved');

            if (!empty($search)) {
                $astrologersQuery->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $allAstrologers = $astrologersQuery->get();

            // Calculate performance metrics for each astrologer
            $performanceList = $allAstrologers->map(function ($astro) use ($dateFilter) {
                try {
                    $providerUserId = $astro->user_id;

                    // Calls metrics
                    $callsQuery = CallSession::where('provider_id', $providerUserId);
                    if ($dateFilter) {
                        $callsQuery->where('created_at', '>=', $dateFilter);
                    }
                    $totalCalls = (clone $callsQuery)->count();
                    $completedCalls = (clone $callsQuery)->where('status', 'completed')->count();
                    $missedCalls = (clone $callsQuery)->whereIn('status', ['rejected', 'missed', 'cancelled'])->count();
                    $callRevenue = (float) (clone $callsQuery)->where('status', 'completed')->sum('total_cost');

                    // Chats metrics
                    $chatsQuery = ChatSession::where('provider_id', $providerUserId);
                    if ($dateFilter) {
                        $chatsQuery->where('created_at', '>=', $dateFilter);
                    }
                    $totalChats = (clone $chatsQuery)->count();
                    $completedChats = (clone $chatsQuery)->where('status', 'completed')->count();
                    $missedChats = (clone $chatsQuery)->whereIn('status', ['rejected', 'missed', 'cancelled'])->count();
                    $chatRevenue = (float) (clone $chatsQuery)->where('status', 'completed')->sum('total_cost');

                    $totalSessions = $totalCalls + $totalChats;
                    $completedSessions = $completedCalls + $completedChats;
                    $missedSessions = $missedCalls + $missedChats;
                    $totalRevenue = $callRevenue + $chatRevenue;

                    $efficiencyRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 100.0;
                    $avgRate = max($astro->call_rate_per_minute ?: 15.0, $astro->chat_rate_per_minute ?: 15.0);
                    $estimatedLoss = $missedSessions * ($avgRate * 5); // 5 mins avg missed session loss

                    // Loyal / Repeat clients (clients who had 2+ completed sessions with this astrologer)
                    $loyalClients = 0;
                    try {
                        $loyalClients = DB::table(function ($q) use ($providerUserId, $dateFilter) {
                            $c = DB::table('call_sessions')->select('consumer_id')->where('provider_id', $providerUserId)->where('status', 'completed');
                            $ch = DB::table('chat_sessions')->select('consumer_id')->where('provider_id', $providerUserId)->where('status', 'completed');
                            if ($dateFilter) {
                                $c->where('created_at', '>=', $dateFilter);
                                $ch->where('created_at', '>=', $dateFilter);
                            }
                            $q->from($c->unionAll($ch), 'all_sessions');
                        })
                        ->select('consumer_id', DB::raw('count(*) as session_count'))
                        ->groupBy('consumer_id')
                        ->having('session_count', '>=', 2)
                        ->get()
                        ->count();
                    } catch (Exception $e) {
                        Log::warning("AstrologerPerformanceController loyalClients calculation error for astrologer {$astro->id}: " . $e->getMessage());
                    }

                    return (object) [
                        'id' => $astro->id,
                        'user_id' => $astro->user_id,
                        'name' => $astro->user?->name ?? 'Astrologer #' . $astro->id,
                        'email' => $astro->user?->email ?? '',
                        'phone' => $astro->user?->phone ?? '',
                        'profile_photo' => $astro->user?->profile_photo,
                        'level_name' => $astro->level?->name ?? 'Level ' . ($astro->price_increase_level_id ?? 1) . ' Partner',
                        'total_sessions' => $totalSessions,
                        'completed_sessions' => $completedSessions,
                        'missed_sessions' => $missedSessions,
                        'efficiency_rate' => $efficiencyRate,
                        'total_revenue' => $totalRevenue,
                        'estimated_loss' => $estimatedLoss,
                        'loyal_clients' => $loyalClients,
                        'is_online' => (bool) $astro->is_online,
                        'is_busy' => (bool) $astro->is_busy,
                    ];
                } catch (Exception $e) {
                    Log::error("AstrologerPerformanceController mapping error for astrologer {$astro->id}: " . $e->getMessage());
                    return (object) [
                        'id' => $astro->id,
                        'user_id' => $astro->user_id,
                        'name' => $astro->user?->name ?? 'Astrologer #' . $astro->id,
                        'email' => $astro->user?->email ?? '',
                        'phone' => $astro->user?->phone ?? '',
                        'profile_photo' => $astro->user?->profile_photo,
                        'level_name' => 'Partner',
                        'total_sessions' => 0,
                        'completed_sessions' => 0,
                        'missed_sessions' => 0,
                        'efficiency_rate' => 100.0,
                        'total_revenue' => 0.0,
                        'estimated_loss' => 0.0,
                        'loyal_clients' => 0,
                        'is_online' => (bool) $astro->is_online,
                        'is_busy' => (bool) $astro->is_busy,
                    ];
                }
            });

            // Platform-wide aggregate KPIs
            $totalPlatformSessions = $performanceList->sum('total_sessions');
            $totalPlatformCompleted = $performanceList->sum('completed_sessions');
            $totalPlatformMissed = $performanceList->sum('missed_sessions');
            $totalPlatformRevenue = $performanceList->sum('total_revenue');
            $activeProsCount = $performanceList->where('is_online', true)->count();
            $totalProsCount = $performanceList->count();

            $avgPlatformCompletion = $totalPlatformSessions > 0
                ? round(($totalPlatformCompleted / $totalPlatformSessions) * 100, 1)
                : 100.0;

            $avgPlatformMissed = $totalPlatformSessions > 0
                ? round(($totalPlatformMissed / $totalPlatformSessions) * 100, 1)
                : 0.0;

            $stats = [
                'avg_completion' => $avgPlatformCompletion,
                'total_revenue' => $totalPlatformRevenue,
                'missed_rate' => $avgPlatformMissed,
                'active_pros' => $activeProsCount,
                'total_pros' => $totalProsCount,
            ];

            // Sort by total revenue descending
            $sortedPerformance = $performanceList->sortByDesc('total_revenue')->values();

            // Paginate items
            $page = max(1, (int) $request->query('page', 1));
            $perPage = 15;
            $paginatedItems = $sortedPerformance->slice(($page - 1) * $perPage, $perPage)->values();

            $performance = new LengthAwarePaginator(
                $paginatedItems,
                $sortedPerformance->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'performance' => $performance,
                ]);
            }

            return view('admin.astrologers.performance', compact('stats', 'performance', 'timeRange', 'search'));
        } catch (Exception $e) {
            Log::error('AstrologerPerformanceController::index fatal error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load astrologer performance metrics: ' . $e->getMessage(),
                ], 500);
            }

            $stats = [
                'avg_completion' => 0.0,
                'total_revenue' => 0.0,
                'missed_rate' => 0.0,
                'active_pros' => 0,
                'total_pros' => 0,
            ];
            $performance = new LengthAwarePaginator([], 0, 15, 1, ['path' => $request->url()]);
            $timeRange = 'all';
            $search = '';

            return view('admin.astrologers.performance', compact('stats', 'performance', 'timeRange', 'search'))
                ->with('error', 'Error calculating performance data: ' . $e->getMessage());
        }
    }
}
