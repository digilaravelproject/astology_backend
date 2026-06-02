<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $query = Astrologer::with(['user', 'skill', 'otherDetails'])
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
                if (!$user) {
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
                    $query->whereHas('user', function ($query) {
                        $query->where('is_online', true);
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

            $activeChatProviders = \App\Models\ChatSession::whereIn('status', ['accepted', 'ongoing'])
                ->pluck('provider_id')
                ->toArray();
            $activeCallProviders = \App\Models\CallSession::whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->pluck('provider_id')
                ->toArray();
            $busyProviderIds = array_unique(array_merge($activeChatProviders, $activeCallProviders));

            $astrologers = $query->get()->map(function ($astrologer) use ($busyProviderIds) {
                // Eager loaded avg rating
                $avgRating = $astrologer->reviews_avg_rating;
                $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;
                
                // Get real online status from astrologers table
                $astrologer->is_online = (bool) $astrologer->is_online;

                // Dynamic busy check
                $isBusy = in_array($astrologer->user_id, $busyProviderIds);
                $astrologer->is_busy = $isBusy;
                if ($astrologer->user) {
                    $astrologer->user->is_busy = $isBusy;
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
            Log::error('Astrologer index error: ' . $e->getMessage());

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
            return array_values(array_filter(array_map('trim', $value), fn($item) => $item !== ''));
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), fn($item) => $item !== ''));
        }

        return [];
    }

    /**
     * Get a single astrologer by ID (including charges).
     */
    public function show($id): JsonResponse
    {
        try {
            $astrologer = Astrologer::with(['user', 'skill', 'otherDetails'])
                ->find($id);

            if (!$astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer not found.',
                ], 404);
            }

            // Calculate dynamic average rating from reviews
            $avgRating = \App\Models\AstrologerReview::where('astrologer_id', $astrologer->id)
                ->avg('rating');
            $astrologer->avg_rating = $avgRating ? (float) number_format($avgRating, 2) : 0;

            // Calculate dynamic busy status
            $isChatBusy = \App\Models\ChatSession::where('provider_id', $astrologer->user_id)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isCallBusy = \App\Models\CallSession::where('provider_id', $astrologer->user_id)
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
            Log::error('Astrologer show error: ' . $e->getMessage());
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
            if (!$user || $user->user_type !== 'astrologer') {
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
                'waiting' => ['waiting'],
                'pending' => ['initiated'],
                'rejected' => ['rejected'],
                'cancelled' => ['rejected'],
                'completed' => ['completed'],
            ];

            $statusValues = null;
            if ($statusFilter) {
                $statusValues = $dbStatusMap[strtolower($statusFilter)] ?? [$statusFilter];
            }

            // Chat subquery
            $chats = \Illuminate\Support\Facades\DB::table('chat_sessions')
                ->select([
                    'id',
                    \Illuminate\Support\Facades\DB::raw("'chat' as type"),
                    'consumer_id',
                    'provider_id',
                    'status',
                    'started_at',
                    'ended_at',
                    'duration_seconds',
                    'rate_per_minute',
                    'total_cost',
                    'created_at',
                    'updated_at'
                ])
                ->where('provider_id', $astrologerUserId);

            // Call subquery
            $calls = \Illuminate\Support\Facades\DB::table('call_sessions')
                ->select([
                    'id',
                    \Illuminate\Support\Facades\DB::raw("'call' as type"),
                    'consumer_id',
                    'provider_id',
                    'status',
                    'started_at',
                    'ended_at',
                    'duration_seconds',
                    'rate_per_minute',
                    'total_cost',
                    'created_at',
                    'updated_at'
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

            $total = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as merged"))
                ->mergeBindings($unionQuery)
                ->count();

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $sortOrder = (strtolower($statusFilter) === 'waiting') ? 'asc' : 'desc';

            $results = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as merged"))
                ->mergeBindings($unionQuery)
                ->orderBy('created_at', $sortOrder)
                ->forPage($page, $perPage)
                ->get();

            // Fetch Consumers in a single query
            $consumerIds = $results->pluck('consumer_id')->unique()->toArray();
            $consumers = \App\Models\User::whereIn('id', $consumerIds)->get()->keyBy('id');

            // Fetch Latest Message for Chat Sessions in a single query
            $chatSessionIds = $results->where('type', 'chat')->pluck('id')->toArray();
            $latestMessages = [];
            if (!empty($chatSessionIds)) {
                $latestMessages = \App\Models\Message::whereIn('chat_session_id', $chatSessionIds)
                    ->whereIn('id', function($query) {
                        $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                            ->from('messages')
                            ->groupBy('chat_session_id');
                    })
                    ->get()
                    ->keyBy('chat_session_id');
            }

            // Format order collection
            $formattedOrders = $results->map(function ($item) use ($consumers, $latestMessages) {
                $consumer = $consumers->get($item->consumer_id);
                
                // Calculate queue priority dynamically if waiting
                $queuePosition = null;
                if ($item->status === 'waiting') {
                    $chatBefore = \Illuminate\Support\Facades\DB::table('chat_sessions')
                        ->where('provider_id', $item->provider_id)
                        ->where('status', 'waiting')
                        ->where('created_at', '<', $item->created_at)
                        ->count();
                    $callBefore = \Illuminate\Support\Facades\DB::table('call_sessions')
                        ->where('provider_id', $item->provider_id)
                        ->where('status', 'waiting')
                        ->where('created_at', '<', $item->created_at)
                        ->count();
                    $queuePosition = $chatBefore + $callBefore + 1;
                }

                return [
                    'session_id' => $item->id,
                    'order_id' => $item->id,
                    'user_id' => $item->consumer_id,
                    'user_name' => $consumer->name ?? 'User',
                    'user_profile_image' => $consumer->profile_photo ?? null,
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
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Astrologer getOrders error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch order history.',
            ], 500);
        }
    }
}
