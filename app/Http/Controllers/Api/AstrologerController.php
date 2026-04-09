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

            $astrologers = $query->get();

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
}
