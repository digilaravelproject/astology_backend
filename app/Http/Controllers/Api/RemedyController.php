<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remedy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RemedyController extends Controller
{
    /**
     * List all active remedies.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $langKey = $request->query('language') ?? $request->header('Accept-Language');
            $language = null;
            if ($langKey) {
                $language = \App\Helpers\CacheHelper::remember("language:code:{$langKey}", 86400, function () use ($langKey) {
                    return \App\Models\Language::where('code', $langKey)->orWhere('id', $langKey)->first();
                }, -1800, 1800);
            }

            if (!$language) {
                $language = \App\Helpers\CacheHelper::remember("language:default", 86400, function () {
                    return \App\Models\Language::where('is_active', true)->first() ?? \App\Models\Language::first();
                }, -1800, 1800);
            }

            $cacheKey = $language ? "remedies:lang:{$language->id}" : "remedies:all_active";
            $remedies = \App\Helpers\CacheHelper::remember($cacheKey, 3600, function () use ($language) {
                $query = Remedy::where('is_active', true);
                if ($language) {
                    $query->where('language_id', $language->id);
                }
                return $query->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($remedy) {
                        return [
                            'id' => $remedy->id,
                            'title' => $remedy->title,
                            'description' => $remedy->description,
                            'image' => $remedy->image_url,
                            'image_path' => $remedy->image,
                            'is_active' => $remedy->is_active,
                            'created_at' => $remedy->created_at,
                            'updated_at' => $remedy->updated_at,
                        ];
                    });
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'remedies' => $remedies,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Remedy index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch remedies.'], 500);
        }
    }

    /**
     * Get a single remedy by ID.
     */
    public function show($id): JsonResponse
    {
        try {
            $remedyData = \App\Helpers\CacheHelper::remember("remedy:detail:{$id}", 3600, function () use ($id) {
                $remedy = Remedy::where('id', $id)
                    ->where('is_active', true)
                    ->first();

                if (!$remedy) {
                    return null;
                }

                return [
                    'id' => $remedy->id,
                    'title' => $remedy->title,
                    'description' => $remedy->description,
                    'image' => $remedy->image_url,
                    'image_path' => $remedy->image,
                    'is_active' => $remedy->is_active,
                    'created_at' => $remedy->created_at,
                    'updated_at' => $remedy->updated_at,
                ];
            });

            if (!$remedyData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Remedy not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'remedy' => $remedyData,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Remedy show error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch remedy details.'], 500);
        }
    }
}
