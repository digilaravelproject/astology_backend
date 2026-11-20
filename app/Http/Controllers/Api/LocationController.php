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
     * Strictly restricts search & output to City, State, Country (no establishments/shops/bungalows).
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
            $cacheKey = 'location_search_v3:' . md5(strtolower($query));

            $results = CacheHelper::remember($cacheKey, 86400, function () use ($query) {
                $apiKey = config('services.google_maps.api_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyD5yjQ3qESYKjD86CkCL4rPR0wQpJ0yyc8'));
                $formattedList = [];
                $seen = [];

                // 1. Primary: Google Places API (New) Text Search with strict locality bias
                try {
                    $placesResponse = Http::timeout(8)
                        ->withHeaders([
                            'Content-Type'     => 'application/json',
                            'X-Goog-Api-Key'   => $apiKey,
                            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.addressComponents,places.types',
                        ])
                        ->post('https://places.googleapis.com/v1/places:searchText', [
                            'textQuery'    => $query,
                            'includedType' => 'locality',
                        ]);

                    if ($placesResponse->successful()) {
                        $placesData = $placesResponse->json();
                        if (!empty($placesData['places']) && is_array($placesData['places'])) {
                            foreach ($placesData['places'] as $place) {
                                $this->processPlaceItem($place, $formattedList, $seen);
                            }
                        }
                    }

                    // Fallback for regions/states/countries if locality filter returned no results
                    if (empty($formattedList)) {
                        $placesResponse2 = Http::timeout(8)
                            ->withHeaders([
                                'Content-Type'     => 'application/json',
                                'X-Goog-Api-Key'   => $apiKey,
                                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.addressComponents,places.types',
                            ])
                            ->post('https://places.googleapis.com/v1/places:searchText', [
                                'textQuery' => $query,
                            ]);

                        if ($placesResponse2->successful()) {
                            $placesData2 = $placesResponse2->json();
                            if (!empty($placesData2['places']) && is_array($placesData2['places'])) {
                                foreach ($placesData2['places'] as $place) {
                                    $types = $place['types'] ?? [];
                                    if ($this->isEstablishmentOnly($types)) {
                                        continue;
                                    }
                                    $this->processPlaceItem($place, $formattedList, $seen);
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
                            'address'     => $query,
                            'key'         => $apiKey,
                            'result_type' => 'locality|administrative_area_level_1|administrative_area_level_2|administrative_area_level_3|country',
                        ]);

                        if ($geoResponse->successful()) {
                            $geoData = $geoResponse->json();
                            if (($geoData['status'] ?? '') === 'OK' && !empty($geoData['results']) && is_array($geoData['results'])) {
                                foreach ($geoData['results'] as $item) {
                                    $this->processGeocodingItem($item, $formattedList, $seen);
                                }
                            }
                        }

                        if (empty($formattedList)) {
                            $geoResponse2 = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                                'address' => $query,
                                'key'     => $apiKey,
                            ]);

                            if ($geoResponse2->successful()) {
                                $geoData2 = $geoResponse2->json();
                                if (($geoData2['status'] ?? '') === 'OK' && !empty($geoData2['results']) && is_array($geoData2['results'])) {
                                    foreach ($geoData2['results'] as $item) {
                                        $types = $item['types'] ?? [];
                                        if ($this->isEstablishmentOnly($types)) {
                                            continue;
                                        }
                                        $this->processGeocodingItem($item, $formattedList, $seen);
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

    /**
     * Process a single place item from Google Places API (New).
     */
    private function processPlaceItem(array $place, array &$formattedList, array &$seen): void
    {
        $name = $place['displayName']['text'] ?? '';
        $formattedAddress = $place['formattedAddress'] ?? '';
        $lat = $place['location']['latitude'] ?? null;
        $lng = $place['location']['longitude'] ?? null;
        $components = $place['addressComponents'] ?? [];

        if ($lat !== null && $lng !== null) {
            $cleanAddress = $this->formatCityStateCountry($components, $formattedAddress ?: $name);
            if (empty($cleanAddress)) {
                $cleanAddress = $this->cleanFallbackAddress($formattedAddress ?: $name);
            }

            $lower = strtolower(trim($cleanAddress));
            if ($lower === 'mumbai, maharashtra, india' || $lower === 'mumbai') {
                $lat = 19.0760;
                $lng = 72.8774;
            }

            if (isset($seen[$lower])) {
                return;
            }
            $seen[$lower] = true;

            $formattedList[] = [
                'display_name' => $cleanAddress,
                'address'      => $cleanAddress,
                'latitude'     => (float) $lat,
                'longitude'    => (float) $lng,
                'place_id'     => $place['id'] ?? null,
            ];
        }
    }

    /**
     * Process a single item from Google Geocoding API.
     */
    private function processGeocodingItem(array $item, array &$formattedList, array &$seen): void
    {
        $formattedAddress = $item['formatted_address'] ?? '';
        $lat = $item['geometry']['location']['lat'] ?? null;
        $lng = $item['geometry']['location']['lng'] ?? null;
        $components = $item['address_components'] ?? [];

        if ($formattedAddress && $lat !== null && $lng !== null) {
            $cleanAddress = $this->formatCityStateCountry($components, $formattedAddress);
            if (empty($cleanAddress)) {
                $cleanAddress = $this->cleanFallbackAddress($formattedAddress);
            }

            $lower = strtolower(trim($cleanAddress));
            if ($lower === 'mumbai, maharashtra, india' || $lower === 'mumbai') {
                $lat = 19.0760;
                $lng = 72.8774;
            }

            if (isset($seen[$lower])) {
                return;
            }
            $seen[$lower] = true;

            $formattedList[] = [
                'display_name' => $cleanAddress,
                'address'      => $cleanAddress,
                'latitude'     => (float) $lat,
                'longitude'    => (float) $lng,
                'place_id'     => $item['place_id'] ?? null,
            ];
        }
    }

    /**
     * Determine if a place is strictly an establishment/POI (e.g. shop, salon, hotel, bungalow)
     * and not a city, administrative area, or political division.
     */
    private function isEstablishmentOnly(array $types): bool
    {
        $geoTypes = [
            'locality',
            'sublocality',
            'sublocality_level_1',
            'administrative_area_level_1',
            'administrative_area_level_2',
            'administrative_area_level_3',
            'country',
            'postal_town',
            'political',
        ];

        foreach ($types as $type) {
            if (in_array($type, $geoTypes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format address components into "City, State, Country".
     *
     * @param array $components
     * @param string $fallback
     * @return string
     */
    private function formatCityStateCountry(array $components, string $fallback = ''): string
    {
        if (empty($components)) {
            return $this->cleanFallbackAddress($fallback);
        }

        $city = '';
        $sublocality = '';
        $state = '';
        $country = '';

        foreach ($components as $comp) {
            $name = $comp['longText'] ?? $comp['long_name'] ?? '';
            $types = $comp['types'] ?? [];

            if (empty($name) || !is_array($types)) {
                continue;
            }

            // City detection
            if (in_array('locality', $types, true) || in_array('postal_town', $types, true)) {
                $city = $name;
            } elseif (empty($city) && (in_array('administrative_area_level_3', $types, true) || in_array('administrative_area_level_2', $types, true))) {
                $city = $name;
            } elseif (empty($sublocality) && (in_array('sublocality', $types, true) || in_array('sublocality_level_1', $types, true))) {
                $sublocality = $name;
            }

            // State / Region
            if (in_array('administrative_area_level_1', $types, true)) {
                $state = $name;
            }

            // Country
            if (in_array('country', $types, true)) {
                $country = $name;
            }
        }

        if (empty($city) && !empty($sublocality)) {
            $city = $sublocality;
        }

        $parts = [];
        if (!empty($city)) {
            $parts[] = $city;
        }
        if (!empty($state) && !in_array($state, $parts, true)) {
            $parts[] = $state;
        }
        if (!empty($country) && !in_array($country, $parts, true)) {
            $parts[] = $country;
        }

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        return $this->cleanFallbackAddress($fallback);
    }

    /**
     * Clean fallback address by removing postal codes and extra punctuation.
     *
     * @param string $address
     * @return string
     */
    private function cleanFallbackAddress(string $address): string
    {
        if (empty($address)) {
            return '';
        }

        // Remove postal / pin codes (4 to 10 digits)
        $clean = preg_replace('/\b\d{4,10}\b/', '', $address);
        // Normalize commas and whitespaces
        $clean = preg_replace('/\s*,\s*/', ', ', $clean);
        $clean = preg_replace('/,{2,}/', ',', $clean);
        $clean = trim($clean, ", \t\n\r\0\x0B");

        return $clean ?: $address;
    }
}
