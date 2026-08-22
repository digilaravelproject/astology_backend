<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveAstrologerMonitorController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $statusFilter = $request->query('status'); // 'busy', 'available', 'online', 'all'

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

            // Get currently ongoing calls and chats safely
            $ongoingCalls = collect();
            $ongoingChats = collect();

            try {
                $ongoingCalls = CallSession::with('consumer')
                    ->where('status', 'ongoing')
                    ->get()
                    ->keyBy('provider_id');
            } catch (Exception $e) {
                Log::warning('LiveAstrologerMonitorController ongoingCalls query error: ' . $e->getMessage());
            }

            try {
                $ongoingChats = ChatSession::with('consumer')
                    ->where('status', 'ongoing')
                    ->get()
                    ->keyBy('provider_id');
            } catch (Exception $e) {
                Log::warning('LiveAstrologerMonitorController ongoingChats query error: ' . $e->getMessage());
            }

            $liveList = $allAstrologers->map(function ($astro) use ($ongoingCalls, $ongoingChats) {
                try {
                    $providerUserId = $astro->user_id;

                    $activeCall = $ongoingCalls->get($providerUserId);
                    $activeChat = $ongoingChats->get($providerUserId);

                    $isBusy = false;
                    $workflow = 'Available for Consultation';
                    $sessionType = null;
                    $clientName = null;
                    $startedAt = null;
                    $ratePerMinute = 0.0;

                    if ($activeCall) {
                        $isBusy = true;
                        $sessionType = ucfirst($activeCall->call_type ?: 'audio') . ' Call';
                        $clientName = $activeCall->consumer?->name ?? 'User #' . $activeCall->consumer_id;
                        $workflow = "{$sessionType}: {$clientName}";
                        $startedAt = $activeCall->started_at;
                        $ratePerMinute = (float) $activeCall->rate_per_minute;
                    } elseif ($activeChat) {
                        $isBusy = true;
                        $sessionType = 'Chat';
                        $clientName = $activeChat->consumer?->name ?? 'User #' . $activeChat->consumer_id;
                        $workflow = "Chat: {$clientName}";
                        $startedAt = $activeChat->started_at ?? $activeChat->accepted_at;
                        $ratePerMinute = (float) $activeChat->rate_per_minute;
                    } elseif ($astro->is_busy) {
                        $isBusy = true;
                        $workflow = 'Busy (In Consultation)';
                    }

                    $elapsedSeconds = $startedAt ? Carbon::now()->diffInSeconds($startedAt) : 0;
                    $elapsedFormatted = $startedAt
                        ? sprintf('%02d:%02d', floor($elapsedSeconds / 60), $elapsedSeconds % 60)
                        : '00:00';

                    $liveStatus = 'Offline';
                    if ($astro->is_online) {
                        $liveStatus = $isBusy ? 'Busy' : 'Available';
                    }

                    return (object) [
                        'id' => $astro->id,
                        'user_id' => $astro->user_id,
                        'name' => $astro->user?->name ?? 'Astrologer #' . $astro->id,
                        'email' => $astro->user?->email ?? '',
                        'phone' => $astro->user?->phone ?? '',
                        'profile_photo' => $astro->user?->profile_photo,
                        'level_name' => $astro->level?->name ?? 'Level ' . ($astro->price_increase_level_id ?? 1) . ' Partner',
                        'is_online' => (bool) $astro->is_online,
                        'is_busy' => $isBusy,
                        'status' => $liveStatus,
                        'workflow' => $workflow,
                        'session_type' => $sessionType,
                        'client_name' => $clientName,
                        'elapsed_time' => $elapsedFormatted,
                        'rating' => (float) ($astro->rating ?: 4.8),
                        'rate_per_minute' => $ratePerMinute,
                        'call_enabled' => (bool) ($astro->is_call_enabled ?? $astro->call_enabled),
                        'chat_enabled' => (bool) ($astro->is_chat_enabled ?? $astro->chat_enabled),
                    ];
                } catch (Exception $e) {
                    Log::error("LiveAstrologerMonitorController mapping error for astrologer {$astro->id}: " . $e->getMessage());
                    return (object) [
                        'id' => $astro->id,
                        'user_id' => $astro->user_id,
                        'name' => $astro->user?->name ?? 'Astrologer #' . $astro->id,
                        'email' => '',
                        'phone' => '',
                        'profile_photo' => null,
                        'level_name' => 'Partner',
                        'is_online' => false,
                        'is_busy' => false,
                        'status' => 'Offline',
                        'workflow' => 'Unavailable',
                        'session_type' => null,
                        'client_name' => null,
                        'elapsed_time' => '00:00',
                        'rating' => 4.8,
                        'rate_per_minute' => 0.0,
                        'call_enabled' => false,
                        'chat_enabled' => false,
                    ];
                }
            });

            // Filter status if requested
            if ($statusFilter === 'busy') {
                $filteredList = $liveList->where('status', 'Busy')->values();
            } elseif ($statusFilter === 'available') {
                $filteredList = $liveList->where('status', 'Available')->values();
            } elseif ($statusFilter === 'online') {
                $filteredList = $liveList->where('is_online', true)->values();
            } else {
                // Default: online first, then busy, then others
                $filteredList = $liveList->sortByDesc(function ($item) {
                    if ($item->is_busy) return 3;
                    if ($item->is_online) return 2;
                    return 1;
                })->values();
            }

            // Pulse summary stats
            $totalLive = $liveList->where('is_online', true)->count();
            $inSession = $liveList->where('is_busy', true)->count();
            $idleReady = max(0, $totalLive - $inSession);
            $revenueVelocity = $liveList->where('is_busy', true)->sum('rate_per_minute');

            $activeCallsCount = $ongoingCalls->count();
            $activeChatsCount = $ongoingChats->count();

            $stats = [
                'total_live' => $totalLive,
                'in_session' => $inSession,
                'idle_ready' => $idleReady,
                'revenue_velocity' => $revenueVelocity,
                'active_calls' => $activeCallsCount,
                'active_chats' => $activeChatsCount,
            ];

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'live_astrologers' => $filteredList,
                ]);
            }

            return view('admin.astrologers.live', compact('stats', 'filteredList', 'search', 'statusFilter'));
        } catch (Exception $e) {
            Log::error('LiveAstrologerMonitorController::index fatal error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->query('format') === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to monitor live astrologers: ' . $e->getMessage(),
                ], 500);
            }

            $stats = [
                'total_live' => 0,
                'in_session' => 0,
                'idle_ready' => 0,
                'revenue_velocity' => 0.0,
                'active_calls' => 0,
                'active_chats' => 0,
            ];
            $filteredList = collect();
            $search = '';
            $statusFilter = 'all';

            return view('admin.astrologers.live', compact('stats', 'filteredList', 'search', 'statusFilter'))
                ->with('error', 'Failed to monitor live astrologers: ' . $e->getMessage());
        }
    }
}
