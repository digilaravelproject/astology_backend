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
     * Search locations/places via Google Places API (New) & Geocoding API.
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
                $formattedList = [];

                // 1. Primary: Google Places API (New) Text Search
                try {
                    $placesResponse = Http::timeout(8)
                        ->withHeaders([
                            'Content-Type'     => 'application/json',
                            'X-Goog-Api-Key'   => $apiKey,
                            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location',
                        ])
                        ->post('https://places.googleapis.com/v1/places:searchText', [
                            'textQuery' => $query,
                        ]);

                    if ($placesResponse->successful()) {
                        $placesData = $placesResponse->json();
                        if (!empty($placesData['places']) && is_array($placesData['places'])) {
                            foreach ($placesData['places'] as $place) {
                                $name = $place['displayName']['text'] ?? '';
                                $formattedAddress = $place['formattedAddress'] ?? '';
                                $lat = $place['location']['latitude'] ?? null;
                                $lng = $place['location']['longitude'] ?? null;

                                if ($lat !== null && $lng !== null) {
                                    $displayName = $formattedAddress ?: $name;
                                    $lower = strtolower(trim($displayName));
                                    if ($lower === 'mumbai, maharashtra, india' || $lower === 'mumbai') {
                                        $lat = 19.0760;
                                        $lng = 72.8774;
                                    }

                                    $formattedList[] = [
                                        'display_name' => $displayName,
                                        'address'      => $formattedAddress ?: $name,
                                        'latitude'     => (float) $lat,
                                        'longitude'    => (float) $lng,
                                        'place_id'     => $place['id'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $pe) {
                    Log::warning('Places New search exception: ' . $pe->getMessage());
                }

                // 2. Fallback: Google Geocoding API if Places API (New) returned no results
                if (empty($formattedList)) {
                    try {
                        $geoResponse = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                            'address' => $query,
                            'key'     => $apiKey,
                        ]);

                        if ($geoResponse->successful()) {
                            $geoData = $geoResponse->json();
                            $status = $geoData['status'] ?? '';

                            if ($status === 'OK' && !empty($geoData['results']) && is_array($geoData['results'])) {
                                foreach ($geoData['results'] as $item) {
                                    $formattedAddress = $item['formatted_address'] ?? '';
                                    $lat = $item['geometry']['location']['lat'] ?? null;
                                    $lng = $item['geometry']['location']['lng'] ?? null;

                                    if ($formattedAddress && $lat !== null && $lng !== null) {
                                        $lower = strtolower(trim($formattedAddress));
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
                            }
                        }
                    } catch (\Throwable $ge) {
                        Log::warning('Geocoding search exception: ' . $ge->getMessage());
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
