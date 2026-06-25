<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerReview;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Services\PricingCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AstrologerController extends Controller
{
    /**
     * List astrologers with their rates/charges.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Astrologer::with([
                'user',
                'skill',
                'otherDetails',
                'offers' => function ($q) {
                    $q->wherePivot('status', 'active')
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', Carbon::now());
                        });
                },
            ])
                ->withAvg('reviews', 'rating');

            $type = $request->query('type', 'all');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');
            $skills = $this->normalizeArrayQueryParam($request->query('skills'));
            $languages = $this->normalizeArrayQueryParam($request->query('language'));
            $minRating = $request->query('min_rating');
            $isOnline = $request->query('is_online');
            $sortBy = $request->query('sort_by');
            $searchQuery = $request->query('search_query');
            $priceColumn = $this->getPriceColumn();
            $user = Auth::guard('sanctum')->user();

            if ($type === 'favourite') {
                if (! $user) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Authentication is required to fetch favourite astrologers.',
                    ], 401);
                }

                $query->whereHas('community', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('is_liked', true);
                });
            } elseif ($type === 'new') {
                $query->where('created_at', '>=', now()->subDays(30));
            }

            if ($priceColumn && $minPrice !== null) {
                $query->where($priceColumn, '>=', (float) $minPrice);
            }

            if ($priceColumn && $maxPrice !== null) {
                $query->where($priceColumn, '<=', (float) $maxPrice);
            }

            if (! empty($skills)) {
                $query->where(function ($query) use ($skills) {
                    foreach ($skills as $skill) {
                        $query->orWhereJsonContains('areas_of_expertise', $skill);
                    }
                });
            }

            if (! empty($languages)) {
                $query->where(function ($query) use ($languages) {
                    foreach ($languages as $language) {
                        $query->orWhereJsonContains('languages', $language);
                    }
                });
            }

            if ($minRating !== null) {
                $query->whereRaw(
                    '(select avg(rating) from astrologer_reviews where astrologer_reviews.astrologer_id = astrologers.id) >= ?',
                    [(float) $minRating]
                );
            }

            if ($isOnline !== null && in_array((string) $isOnline, ['0', '1'], true)) {
                if ((int) $isOnline === 1) {
                    $query->where(function ($query) {
                        $query->where('is_chat_enabled', true)
                            ->orWhere('is_call_enabled', true);
                    });
                }
            }

            if ($searchQuery) {
                $query->whereHas('user', function ($query) use ($searchQuery) {
                    $query->where('name', 'like', '%'.$searchQuery.'%');
                });
            }

            switch ($sortBy) {
                case 'price_low_to_high':
                    if ($priceColumn) {
                        $query->orderBy($priceColumn, 'asc');
                    }
                    break;
                case 'price_high_to_low':
                    if ($priceColumn) {
                        $query->orderBy($priceColumn, 'desc');
                    }
                    break;
                case 'experience_high_to_low':
                    $query->orderBy('years_of_experience', 'desc');
                    break;
                case 'rating_high_to_low':
                    $query->orderByDesc('reviews_avg_rating');
                    break;
                default:
                    $query->orderBy('id', 'desc');
                    break;
            }

            $activeChatProviders = ChatSession::whereIn('status', ['accepted', 'ongoing'])
                ->pluck('provider_id')
                ->toArray();
            $activeCallProviders = CallSession::whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->pluck('provider_id')
                ->toArray();
            $busyProviderIds = array_unique(array_merge($activeChatProviders, $activeCallProviders));

            $pricingCalculator = app(PricingCalculatorService::class);
            $astrologers = $query->get()->map(function ($astrologer) use ($busyProviderIds, $pricingCalculator) {
                // Eager loaded avg rating
                $avgRating = $astrologer->reviews_avg_rating;
                $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;

                // Get availability flags from astrologers table
                $astrologer->is_online = (bool) ($astrologer->is_chat_enabled || $astrologer->is_call_enabled);
                $astrologer->is_chat_enabled = (bool) $astrologer->is_chat_enabled;
                $astrologer->is_call_enabled = (bool) $astrologer->is_call_enabled;

                // Dynamic busy check
                $isBusy = in_array($astrologer->user_id, $busyProviderIds);
                $astrologer->is_busy = $isBusy;
                if ($astrologer->user) {
                    $astrologer->user->is_busy = $isBusy;
                }

                // Dynamic Pricing Calculation
                $chatPricing = $pricingCalculator->calculate($astrologer, 'chat');
                $callPricing = $pricingCalculator->calculate($astrologer, 'call');

                $astrologer->original_chat_rate_per_minute = (float) $astrologer->chat_rate_per_minute;
                $astrologer->original_call_rate_per_minute = (float) $astrologer->call_rate_per_minute;

                // Override current rates with offer rates
                $astrologer->chat_rate_per_minute = (float) $chatPricing['customer_rate'];
                $astrologer->call_rate_per_minute = (float) $callPricing['customer_rate'];

                $astrologer->has_offer = $chatPricing['has_offer'] || $callPricing['has_offer'];
                if ($astrologer->has_offer && $astrologer->offers->isNotEmpty()) {
                    $activeOffer = $astrologer->offers->first();
                    $astrologer->offer_details = [
                        'id' => $activeOffer->id,
                        'name' => $activeOffer->name,
                        'discount_percentage' => (float) $activeOffer->discount_percentage,
                        'expires_at' => $activeOffer->expires_at ? $activeOffer->expires_at->toDateTimeString() : null,
                    ];
                } else {
                    $astrologer->offer_details = null;
                }

                return $astrologer;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologers' => $astrologers,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Astrologer index error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch astrologers.',
            ], 500);
        }
    }

    /**
     * Get the first available astrologer pricing column.
     */
    private function getPriceColumn(): ?string
    {
        if (Schema::hasColumn('astrologers', 'chat_rate_per_minute')) {
            return 'chat_rate_per_minute';
        }

        if (Schema::hasColumn('astrologers', 'call_rate_per_minute')) {
            return 'call_rate_per_minute';
        }

        if (Schema::hasColumn('astrologers', 'video_call_rate_per_minute')) {
            return 'video_call_rate_per_minute';
        }

        return null;
    }

    /**
     * Normalize a query parameter that may be a string or an array.
     */
    private function normalizeArrayQueryParam(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($item) => $item !== ''));
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($item) => $item !== ''));
        }

        return [];
    }

    /**
     * Get a single astrologer by ID (including charges).
     */
    public function show($id): JsonResponse
    {
        try {
            $astrologer = Astrologer::with([
                'user',
                'skill',
                'otherDetails',
                'offers' => function ($q) {
                    $q->wherePivot('status', 'active')
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', Carbon::now());
                        });
                },
            ])->find($id);

            if (! $astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer not found.',
                ], 404);
            }

            // Dynamic Pricing Calculation
            $pricingCalculator = app(PricingCalculatorService::class);
            $chatPricing = $pricingCalculator->calculate($astrologer, 'chat');
            $callPricing = $pricingCalculator->calculate($astrologer, 'call');

            $astrologer->original_chat_rate_per_minute = (float) $astrologer->chat_rate_per_minute;
            $astrologer->original_call_rate_per_minute = (float) $astrologer->call_rate_per_minute;

            // Override current rates with offer rates
            $astrologer->chat_rate_per_minute = (float) $chatPricing['customer_rate'];
            $astrologer->call_rate_per_minute = (float) $callPricing['customer_rate'];

            $astrologer->has_offer = $chatPricing['has_offer'] || $callPricing['has_offer'];
            if ($astrologer->has_offer && $astrologer->offers->isNotEmpty()) {
                $activeOffer = $astrologer->offers->first();
                $astrologer->offer_details = [
                    'id' => $activeOffer->id,
                    'name' => $activeOffer->name,
                    'discount_percentage' => (float) $activeOffer->discount_percentage,
                    'expires_at' => $activeOffer->expires_at ? $activeOffer->expires_at->toDateTimeString() : null,
                ];
            } else {
                $astrologer->offer_details = null;
            }

            // Calculate dynamic average rating from reviews
            $avgRating = AstrologerReview::where('astrologer_id', $astrologer->id)
                ->avg('rating');
            $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;

            // Calculate dynamic busy status
            $isChatBusy = ChatSession::where('provider_id', $astrologer->user_id)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isCallBusy = CallSession::where('provider_id', $astrologer->user_id)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            $isBusy = $isChatBusy || $isCallBusy;
            $astrologer->is_busy = $isBusy;
            if ($astrologer->user) {
                $astrologer->user->is_busy = $isBusy;
            }

            $user = Auth::guard('sanctum')->user();

            if ($user) {
                $community = AstrologerCommunity::where('astrologer_id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                $astrologer->is_followed = $community ? $community->is_liked : false;
                $astrologer->is_blocked = $community ? $community->is_blocked : false;
            } else {
                $astrologer->is_followed = false;
                $astrologer->is_blocked = false;
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'astrologer' => $astrologer,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Astrologer show error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to fetch astrologer details.'], 500);
        }
    }

    /**
     * Get unified order history / waiting list for the logged-in astrologer.
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user || $user->user_type !== 'astrologer') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            $astrologerUserId = $user->id;

            $statusFilter = $request->query('status'); // e.g. waiting, pending, completed, rejected, cancelled
            $typeFilter = $request->query('type'); // e.g. chat, call

            // Map common requested terms to db status
            $dbStatusMap = [
                'waiting' => ['waiting', 'initiated'],
                'pending' => ['initiated'],
                'rejected' => ['rejected'],
                'cancelled' => ['cancelled'],
                'completed' => ['completed'],
            ];

            $statusValues = null;
            if ($statusFilter) {
                $statusValues = $dbStatusMap[strtolower($statusFilter)] ?? [$statusFilter];
            }

            // Chat subquery
            $chats = DB::table('chat_sessions')
                ->select([
                    'id',
                    DB::raw("'chat' as type"),
                    'consumer_id',
                    'provider_id',
                    'status',
                    'started_at',
                    'ended_at',
                    'duration_seconds',
                    'rate_per_minute',
                    'total_cost',
                    'created_at',
                    'updated_at',
                ])
                ->where('provider_id', $astrologerUserId);

            // Call subquery
            $calls = DB::table('call_sessions')
                ->select([
                    'id',
                    DB::raw("'call' as type"),
                    'consumer_id',
                    'provider_id',
                    'status',
                    'started_at',
                    'ended_at',
                    'duration_seconds',
                    'rate_per_minute',
                    'total_cost',
                    'created_at',
                    'updated_at',
                ])
                ->where('provider_id', $astrologerUserId);

            if ($statusValues) {
                $chats->whereIn('status', $statusValues);
                $calls->whereIn('status', $statusValues);
            }

            // Union logic based on type filter
            if ($typeFilter === 'chat') {
                $unionQuery = $chats;
            } elseif ($typeFilter === 'call') {
                $unionQuery = $calls;
            } else {
                $unionQuery = $chats->unionAll($calls);
            }

            $total = DB::table(DB::raw("({$unionQuery->toSql()}) as merged"))
                ->mergeBindings($unionQuery)
                ->count();

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $sortOrder = (strtolower($statusFilter) === 'waiting') ? 'asc' : 'desc';

            $results = DB::table(DB::raw("({$unionQuery->toSql()}) as merged"))
                ->mergeBindings($unionQuery)
                ->orderBy('created_at', $sortOrder)
                ->forPage($page, $perPage)
                ->get();

            // Fetch Consumers in a single query
            $consumerIds = $results->pluck('consumer_id')->unique()->toArray();
            $consumers = User::whereIn('id', $consumerIds)->get()->keyBy('id');

            // Fetch Latest Message for Chat Sessions in a single query
            $chatSessionIds = $results->where('type', 'chat')->pluck('id')->toArray();
            $latestMessages = [];
            if (! empty($chatSessionIds)) {
                $latestMessages = Message::whereIn('chat_session_id', $chatSessionIds)
                    ->whereIn('id', function ($query) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('messages')
                            ->groupBy('chat_session_id');
                    })
                    ->get()
                    ->keyBy('chat_session_id');
            }

            // Fetch waiting positions globally to prevent N+1 query loops
            $waitingPositions = [];
            $hasWaiting = $results->contains('status', 'waiting');
            if ($hasWaiting) {
                $waitingChats = DB::table('chat_sessions')
                    ->select(['id', 'created_at', DB::raw("'chat' as type")])
                    ->where('provider_id', $astrologerUserId)
                    ->where('status', 'waiting');

                $waitingCalls = DB::table('call_sessions')
                    ->select(['id', 'created_at', DB::raw("'call' as type")])
                    ->where('provider_id', $astrologerUserId)
                    ->where('status', 'waiting');

                $allWaiting = $waitingChats->unionAll($waitingCalls)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($allWaiting as $index => $wItem) {
                    $waitingPositions[$wItem->type.'_'.$wItem->id] = $index + 1;
                }
            }

            // Format order collection
            $formattedOrders = $results->map(function ($item) use ($consumers, $latestMessages, $waitingPositions) {
                $consumer = $consumers->get($item->consumer_id);

                // Calculate queue priority dynamically if waiting
                $queuePosition = null;
                if ($item->status === 'waiting') {
                    $key = $item->type.'_'.$item->id;
                    $queuePosition = $waitingPositions[$key] ?? 1;
                }

                return [
                    'session_id' => $item->id,
                    'order_id' => $item->id,
                    'user_id' => $item->consumer_id,
                    'user_name' => $consumer->name ?? 'User',
                    'user_profile_image' => $consumer ? MediaHelper::getUrl($consumer->profile_photo) : null,
                    'request_type' => $item->type,
                    'status' => $item->status,
                    'requested_at' => $item->created_at,
                    'started_at' => $item->started_at,
                    'ended_at' => $item->ended_at,
                    'duration_seconds' => (int) $item->duration_seconds,
                    'amount' => (float) $item->total_cost,
                    'rate_per_minute' => (float) $item->rate_per_minute,
                    'payment_status' => $item->status === 'completed' ? 'paid' : ($item->total_cost > 0 ? 'paid' : 'pending'),
                    'last_message' => ($item->type === 'chat' && isset($latestMessages[$item->id])) ? $latestMessages[$item->id]->message : null,
                    'queue_position' => $queuePosition,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully.',
                'data' => [
                    'orders' => $formattedOrders,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => (int) $perPage,
                        'current_page' => (int) $page,
                        'last_page' => (int) ceil($total / $perPage),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Astrologer getOrders error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch order history.',
            ], 500);
        }
    }

    /**
     * Get dynamic performance metrics for the authenticated astrologer.
     */
    public function getPerformance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $astrologer = $user->astrologer;

            if (! $astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $astrologerId = $astrologer->id;
            $userId = $user->id;

            // 1. Profile Badge (Rising Star, Top Choice, Celebrity)
            $totalBusyMinutes = $astrologer->total_busy_minutes;
            if ($totalBusyMinutes < 1000) {
                $badgeType = 'Rising Star';
            } elseif ($totalBusyMinutes < 5000) {
                $badgeType = 'Top Choice';
            } else {
                $badgeType = 'Celebrity';
            }

            // 2. Today's Profile Health (Stats for today)
            $today = Carbon::today();

            $todayCallsCount = CallSession::where('provider_id', $userId)
                ->whereDate('created_at', $today)
                ->count();
            $todayChatsCount = ChatSession::where('provider_id', $userId)
                ->whereDate('created_at', $today)
                ->count();
            $totalSessionsToday = $todayCallsCount + $todayChatsCount;

            $missedCallsToday = CallSession::where('provider_id', $userId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['missed', 'failed', 'rejected'])
                ->count();
            $missedChatsToday = ChatSession::where('provider_id', $userId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['rejected'])
                ->count();
            $missedSessionsToday = $missedCallsToday + $missedChatsToday;

            $callRate = (float) ($astrologer->call_rate_per_minute ?? 0);
            $chatRate = (float) ($astrologer->chat_rate_per_minute ?? 0);
            $revenueLossToday = ($missedCallsToday * $callRate * 5) + ($missedChatsToday * $chatRate * 5);

            // 3. Availability and Busy Minutes
            $busyMinsToday = (float) (CallSession::where('provider_id', $userId)
                ->whereIn('status', ['completed', 'approved'])
                ->whereDate('created_at', $today)
                ->sum('duration_seconds') +
                ChatSession::where('provider_id', $userId)
                    ->whereIn('status', ['completed', 'approved'])
                    ->whereDate('created_at', $today)
                    ->sum('duration_seconds')) / 60;

            $sevenDaysAgo = Carbon::today()->subDays(6);
            $busyMins7Days = (float) (CallSession::where('provider_id', $userId)
                ->whereIn('status', ['completed', 'approved'])
                ->where('created_at', '>=', $sevenDaysAgo)
                ->sum('duration_seconds') +
                ChatSession::where('provider_id', $userId)
                    ->whereIn('status', ['completed', 'approved'])
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->sum('duration_seconds')) / 60;

            $thirtyDaysAgo = Carbon::today()->subDays(29);
            $busyMins30Days = (float) (CallSession::where('provider_id', $userId)
                ->whereIn('status', ['completed', 'approved'])
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum('duration_seconds') +
                ChatSession::where('provider_id', $userId)
                    ->whereIn('status', ['completed', 'approved'])
                    ->where('created_at', '>=', $thirtyDaysAgo)
                    ->sum('duration_seconds')) / 60;

            $availability = $astrologer->availability ?? [];

            $getScheduledMinutesForDay = function ($dayName) use ($availability) {
                $dayName = strtolower($dayName);
                foreach ($availability as $entry) {
                    if (isset($entry['day']) && strtolower($entry['day']) === $dayName && ! empty($entry['enabled'])) {
                        $mins = 0;
                        if (isset($entry['slots']) && is_array($entry['slots'])) {
                            foreach ($entry['slots'] as $slot) {
                                if (! empty($slot['start']) && ! empty($slot['end'])) {
                                    try {
                                        $start = Carbon::createFromFormat('H:i', $slot['start']);
                                        $end = Carbon::createFromFormat('H:i', $slot['end']);
                                        if ($end->greaterThan($start)) {
                                            $mins += $end->diffInMinutes($start);
                                        }
                                    } catch (\Exception $e) {
                                        // Ignore malformed time slots
                                    }
                                }
                            }
                        }

                        return $mins;
                    }
                }

                return 0;
            };

            $todayDayName = strtolower(Carbon::today()->format('l'));
            $availableMinsToday = $getScheduledMinutesForDay($todayDayName);

            $availableMins7Days = 0;
            for ($i = 0; $i < 7; $i++) {
                $dayName = strtolower(Carbon::today()->subDays($i)->format('l'));
                $availableMins7Days += $getScheduledMinutesForDay($dayName);
            }

            $availableMins30Days = 0;
            for ($i = 0; $i < 30; $i++) {
                $dayName = strtolower(Carbon::today()->subDays($i)->format('l'));
                $availableMins30Days += $getScheduledMinutesForDay($dayName);
            }

            // 4. Loyal User Conversion
            $callUsers = CallSession::where('provider_id', $userId)
                ->pluck('consumer_id')
                ->toArray();
            $chatUsers = ChatSession::where('provider_id', $userId)
                ->pluck('consumer_id')
                ->toArray();
            $allUsers = array_unique(array_merge($callUsers, $chatUsers));
            $totalUsersCount = count($allUsers);

            $completedCalls = CallSession::where('provider_id', $userId)
                ->whereIn('status', ['completed', 'approved'])
                ->select('consumer_id', DB::raw('count(*) as total'))
                ->groupBy('consumer_id')
                ->get()
                ->pluck('total', 'consumer_id')
                ->toArray();

            $completedChats = ChatSession::where('provider_id', $userId)
                ->whereIn('status', ['completed', 'approved'])
                ->select('consumer_id', DB::raw('count(*) as total'))
                ->groupBy('consumer_id')
                ->get()
                ->pluck('total', 'consumer_id')
                ->toArray();

            $userSessionCounts = [];
            foreach ($completedCalls as $cId => $count) {
                $userSessionCounts[$cId] = ($userSessionCounts[$cId] ?? 0) + $count;
            }
            foreach ($completedChats as $cId => $count) {
                $userSessionCounts[$cId] = ($userSessionCounts[$cId] ?? 0) + $count;
            }

            $loyalUsersCount = 0;
            foreach ($userSessionCounts as $cId => $count) {
                if ($count >= 2) {
                    $loyalUsersCount++;
                }
            }

            $loyalUserConversionRate = $totalUsersCount > 0
                ? round(($loyalUsersCount / $totalUsersCount) * 100, 1)
                : 0.0;

            if ($loyalUsersCount < 10) {
                $loyalUserLevel = 1;
            } elseif ($loyalUsersCount < 50) {
                $loyalUserLevel = 2;
            } elseif ($loyalUsersCount < 100) {
                $loyalUserLevel = 3;
            } else {
                $loyalUserLevel = 4;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Astrologer performance data retrieved successfully.',
                'data' => [
                    'badge_type' => $badgeType,
                    'profile_health' => [
                        'date' => Carbon::today()->format('d F Y'),
                        'total_sessions' => $totalSessionsToday,
                        'missed_sessions' => $missedSessionsToday,
                        'revenue_loss' => round($revenueLossToday, 2),
                        'missed_calls' => $missedCallsToday,
                        'missed_chats' => $missedChatsToday,
                        'loyal_users' => $loyalUsersCount,
                    ],
                    'availability' => [
                        'available_mins' => [
                            'today' => round($availableMinsToday, 0),
                            'seven_days' => round($availableMins7Days, 0),
                            'thirty_days' => round($availableMins30Days, 0),
                        ],
                        'busy_mins' => [
                            'today' => round($busyMinsToday, 0),
                            'seven_days' => round($busyMins7Days, 0),
                            'thirty_days' => round($busyMins30Days, 0),
                        ],
                    ],
                    'loyal_user_conversion' => [
                        'conversion_percentage' => $loyalUserConversionRate,
                        'total_users' => $totalUsersCount,
                        'loyal_users' => $loyalUsersCount,
                        'loyal_user_level' => $loyalUserLevel,
                    ],
                    'today_progress' => [
                        'target_hours' => (float) Setting::get('astrologer_daily_target_hours', 8.0),
                        'completed_minutes' => round($busyMinsToday, 0),
                        'remaining_hours' => round(max(0.0, (float) Setting::get('astrologer_daily_target_hours', 8.0) - (round($busyMinsToday, 0) / 60.0)), 1),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Astrologer getPerformance error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch performance data.',
            ], 500);
        }
    }
}
