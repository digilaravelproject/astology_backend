<?php

namespace App\Services;

use App\Helpers\MediaHelper;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerReview;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Models\Package;
use App\Models\AstrologerPackage;
use App\Models\PackagePurchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AstrologerService
{
    public function __construct(
        private readonly PricingCalculatorService $pricingCalculator
    ) {}

    /**
     * List astrologers with filters and pricing overrides.
     */
    public function listAstrologers(array $filters, ?User $currentUser): array
    {
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

        $type = $filters['type'] ?? 'all';
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $skills = $this->normalizeArrayQueryParam($filters['skills'] ?? null);
        $languages = $this->normalizeArrayQueryParam($filters['language'] ?? null);
        $minRating = $filters['min_rating'] ?? null;
        $isOnline = $filters['is_online'] ?? null;
        $sortBy = $filters['sort_by'] ?? null;
        $searchQuery = $filters['search_query'] ?? null;
        $priceColumn = $this->getPriceColumn();

        if ($type === 'favourite') {
            if (!$currentUser) {
                throw new \InvalidArgumentException('Authentication is required to fetch favourite astrologers.', 401);
            }

            $query->whereHas('community', function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id)
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

        if (!empty($skills)) {
            $query->where(function ($query) use ($skills) {
                foreach ($skills as $skill) {
                    $query->orWhereJsonContains('areas_of_expertise', $skill);
                }
            });
        }

        if (!empty($languages)) {
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
                $query->where('name', 'like', '%' . $searchQuery . '%');
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

        $rawAstrologers = $query->get();
        $astrologerUserIds = $rawAstrologers->pluck('user_id')->toArray();
        $customPackages = AstrologerPackage::whereIn('astrologer_id', $astrologerUserIds)->get()->keyBy('astrologer_id');
        $defaultPackage = Package::where('is_default', true)->first();

        $activePurchases = collect();
        if ($currentUser) {
            $activePurchases = PackagePurchase::where('user_id', $currentUser->id)
                ->whereIn('astrologer_id', $astrologerUserIds)
                ->where('status', 'active')
                ->get()
                ->keyBy('astrologer_id');
        }

        $astrologers = $rawAstrologers->map(function ($astrologer) use ($busyProviderIds, $customPackages, $defaultPackage, $activePurchases, $currentUser) {
            $avgRating = $astrologer->reviews_avg_rating;
            $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;

            $astrologer->is_online = (bool) ($astrologer->is_chat_enabled || $astrologer->is_call_enabled);
            $astrologer->is_chat_enabled = (bool) $astrologer->is_chat_enabled;
            $astrologer->is_call_enabled = (bool) $astrologer->is_call_enabled;

            $isBusy = in_array($astrologer->user_id, $busyProviderIds);
            $astrologer->is_busy = $isBusy;
            if ($astrologer->user) {
                $astrologer->user->is_busy = $isBusy;
            }

            $chatPricing = $this->pricingCalculator->calculate($astrologer, 'chat');
            $callPricing = $this->pricingCalculator->calculate($astrologer, 'call');

            $astrologer->original_chat_rate_per_minute = (float) $astrologer->chat_rate_per_minute;
            $astrologer->original_call_rate_per_minute = (float) $astrologer->call_rate_per_minute;

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

            // Package details
            $astroPackage = $customPackages->get($astrologer->user_id);
            $purchase = $currentUser ? $activePurchases->get($astrologer->user_id) : null;

            $packageAmount = $astroPackage ? (float) $astroPackage->amount : ($defaultPackage ? (float) $defaultPackage->default_amount : 0.00);
            $packageDuration = $astroPackage ? (int) $astroPackage->duration : ($defaultPackage ? (int) $defaultPackage->default_duration : 0);
            $packageName = $defaultPackage ? $defaultPackage->name : 'Astrology Package';

            $astrologer->package_details = [
                'name' => $packageName,
                'price' => $packageAmount,
                'duration' => $packageDuration,
                'is_purchase' => $purchase ? true : false,
                'used_time' => $purchase ? (int) ($purchase->total_duration - $purchase->remaining_duration) : 0,
                'remaining_time' => $purchase ? (int) $purchase->remaining_duration : 0
            ];

            return $astrologer;
        });

        return ['astrologers' => $astrologers];
    }

    /**
     * Get details of a single astrologer.
     */
    public function getAstrologerDetails(int $id, ?User $currentUser): Astrologer
    {
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

        if (!$astrologer) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Astrologer not found.');
        }

        $chatPricing = $this->pricingCalculator->calculate($astrologer, 'chat');
        $callPricing = $this->pricingCalculator->calculate($astrologer, 'call');

        $astrologer->original_chat_rate_per_minute = (float) $astrologer->chat_rate_per_minute;
        $astrologer->original_call_rate_per_minute = (float) $astrologer->call_rate_per_minute;

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

        $avgRating = AstrologerReview::where('astrologer_id', $astrologer->id)->avg('rating');
        $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;

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

        if ($currentUser) {
            $community = AstrologerCommunity::where('astrologer_id', $id)
                ->where('user_id', $currentUser->id)
                ->first();

            $astrologer->is_followed = $community ? $community->is_liked : false;
            $astrologer->is_blocked = $community ? $community->is_blocked : false;
        } else {
            $astrologer->is_followed = false;
            $astrologer->is_blocked = false;
        }

        // Package details
        $astroPackage = AstrologerPackage::where('astrologer_id', $astrologer->user_id)->first();
        $defaultPackage = Package::where('is_default', true)->first();

        $purchase = null;
        if ($currentUser) {
            $purchase = PackagePurchase::where('user_id', $currentUser->id)
                ->where('astrologer_id', $astrologer->user_id)
                ->where('status', 'active')
                ->first();
        }

        $packageAmount = $astroPackage ? (float) $astroPackage->amount : ($defaultPackage ? (float) $defaultPackage->default_amount : 0.00);
        $packageDuration = $astroPackage ? (int) $astroPackage->duration : ($defaultPackage ? (int) $defaultPackage->default_duration : 0);
        $packageName = $defaultPackage ? $defaultPackage->name : 'Astrology Package';

        $astrologer->package_details = [
            'name' => $packageName,
            'price' => $packageAmount,
            'duration' => $packageDuration,
            'is_purchase' => $purchase ? true : false,
            'used_time' => $purchase ? (int) ($purchase->total_duration - $purchase->remaining_duration) : 0,
            'remaining_time' => $purchase ? (int) $purchase->remaining_duration : 0
        ];

        return $astrologer;
    }

    /**
     * Get order history / waiting list for the astrologer.
     */
    public function getAstrologerOrders(User $user, array $filters): array
    {
        if ($user->user_type !== 'astrologer') {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access.', 403);
        }

        $astrologerUserId = $user->id;
        $statusFilter = $filters['status'] ?? null;
        $typeFilter = $filters['type'] ?? null;
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

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

        $sortOrder = (strtolower($statusFilter) === 'waiting') ? 'asc' : 'desc';

        $results = DB::table(DB::raw("({$unionQuery->toSql()}) as merged"))
            ->mergeBindings($unionQuery)
            ->orderBy('created_at', $sortOrder)
            ->forPage($page, $perPage)
            ->get();

        $consumerIds = $results->pluck('consumer_id')->unique()->toArray();
        $consumers = User::whereIn('id', $consumerIds)->get()->keyBy('id');

        $chatSessionIds = $results->where('type', 'chat')->pluck('id')->toArray();
        $latestMessages = [];
        if (!empty($chatSessionIds)) {
            $latestMessages = Message::whereIn('chat_session_id', $chatSessionIds)
                ->whereIn('id', function ($query) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('messages')
                        ->groupBy('chat_session_id');
                })
                ->get()
                ->keyBy('chat_session_id');
        }

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
                $waitingPositions[$wItem->type . '_' . $wItem->id] = $index + 1;
            }
        }

        $formattedOrders = $results->map(function ($item) use ($consumers, $latestMessages, $waitingPositions) {
            $consumer = $consumers->get($item->consumer_id);
            $queuePosition = null;
            if ($item->status === 'waiting') {
                $key = $item->type . '_' . $item->id;
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

        return [
            'orders' => $formattedOrders,
            'pagination' => [
                'total' => $total,
                'per_page' => (int) $perPage,
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
            ]
        ];
    }

    /**
     * Get dynamic performance metrics.
     */
    public function getPerformanceMetrics(User $user): array
    {
        $astrologer = $user->astrologer;
        if (!$astrologer) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Astrologer profile not found.');
        }

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
                if (isset($entry['day']) && strtolower($entry['day']) === $dayName && !empty($entry['enabled'])) {
                    $mins = 0;
                    if (isset($entry['slots']) && is_array($entry['slots'])) {
                        foreach ($entry['slots'] as $slot) {
                            if (!empty($slot['start']) && !empty($slot['end'])) {
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
        $callUsers = CallSession::where('provider_id', $userId)->pluck('consumer_id')->toArray();
        $chatUsers = ChatSession::where('provider_id', $userId)->pluck('consumer_id')->toArray();
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

        return [
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
        ];
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
}
