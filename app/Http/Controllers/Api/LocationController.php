<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Helpers\CacheHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Search locations/places via Google Geocoding API.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('query', $request->input('address', '')));

        if (empty($query)) {
            return ApiResponse::success([], 'Query is empty');
        }

        try {
            $cacheKey = 'location_search:' . md5(strtolower($query));

            $results = CacheHelper::remember($cacheKey, 86400, function () use ($query) {
                $apiKey = config('services.google_maps.api_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyD5yjQ3qESYKjD86CkCL4rPR0wQpJ0yyc8'));

                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key'     => $apiKey,
                ]);

                if (!$response->successful()) {
                    Log::warning('Google Geocoding HTTP failed: ' . $response->status(), [
                        'query' => $query,
                        'body'  => $response->body(),
                    ]);
                    return [];
                }

                $data = $response->json();
                $status = $data['status'] ?? '';

                if ($status !== 'OK' || empty($data['results']) || !is_array($data['results'])) {
                    if ($status !== 'ZERO_RESULTS') {
                        Log::warning('Google Geocoding non-OK status: ' . $status, [
                            'query'         => $query,
                            'error_message' => $data['error_message'] ?? null,
                        ]);
                    }
                    return [];
                }

                $formattedList = [];

                foreach ($data['results'] as $item) {
                    $formattedAddress = $item['formatted_address'] ?? '';
                    $lat = $item['geometry']['location']['lat'] ?? null;
                    $lng = $item['geometry']['location']['lng'] ?? null;

                    if ($formattedAddress && $lat !== null && $lng !== null) {
                        $lower = strtolower(trim($formattedAddress));
                        // Refine South Mumbai default center to standard Mumbai city center
                        if ($lower === 'mumbai, maharashtra, india' || $lower === 'mumbai') {
                            $lat = 19.0760;
                            $lng = 72.8774;
                        }

                        $formattedList[] = [
                            'display_name' => $formattedAddress,
                            'address'      => $formattedAddress,
                            'latitude'     => (float) $lat,
                            'longitude'    => (float) $lng,
                            'place_id'     => $item['place_id'] ?? null,
                        ];
                    }
                }

                return $formattedList;
            }, -1800, 1800);

            return ApiResponse::success($results, 'Locations retrieved successfully');
        } catch (\Throwable $e) {
            Log::error('Location search error: ' . $e->getMessage(), [
                'query' => $query,
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Unable to fetch location details', 500);
        }
    }
}
