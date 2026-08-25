<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoundersWord;
use App\Models\Language;
use App\Helpers\CacheHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FoundersWordController extends Controller
{
    /**
     * List active founder messages (latest first) translated to the requested language.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $langKey = $request->query('language') ?? $request->header('Accept-Language');
            $language = null;
            if ($langKey) {
                $language = CacheHelper::remember("language:code:{$langKey}", 86400, function () use ($langKey) {
                    return Language::where('code', $langKey)->orWhere('id', $langKey)->first();
                }, -1800, 1800);
            }

            if (!$language) {
                $language = CacheHelper::remember("language:default", 86400, function () {
                    return Language::where('code', 'en')->first() 
                        ?? Language::where('is_active', true)->first() 
                        ?? Language::first();
                }, -1800, 1800);
            }

            $langCode = strtolower($language?->code ?? 'en');
            $cacheKey = "founders_words:lang:{$langCode}";

            $words = CacheHelper::remember($cacheKey, 86400, function () use ($language, $langCode) {
                return FoundersWord::where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($word) use ($language, $langCode) {
                        return [
                            'id' => $word->id,
                            'language_id' => $language?->id ?? $word->language_id,
                            'language_code' => $langCode,
                            'language_name' => $language?->name ?? 'English',
                            'title' => $word->getTranslatedTitle($langCode),
                            'message' => $word->getTranslatedMessage($langCode),
                            'title_en' => $word->title_en ?: $word->title,
                            'message_en' => $word->message_en ?: $word->message,
                            'title_hi' => $word->title_hi,
                            'message_hi' => $word->message_hi,
                            'title_mr' => $word->title_mr,
                            'message_mr' => $word->message_mr,
                            'translations' => $word->translations ?: [
                                'en' => ['title' => $word->title_en ?: $word->title, 'message' => $word->message_en ?: $word->message],
                                'hi' => ['title' => $word->title_hi, 'message' => $word->message_hi],
                                'mr' => ['title' => $word->title_mr, 'message' => $word->message_mr],
                            ],
                            'image' => $word->image_url,
                            'image_path' => $word->image,
                            'is_active' => $word->is_active,
                            'created_at' => $word->created_at,
                            'updated_at' => $word->updated_at,
                        ];
                    });
            }, -1800, 1800);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'founders_words' => $words,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('FoundersWord index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch founder messages.'], 500);
        }
    }

    /**
     * Get a single founder message with translations.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $langKey = $request->query('language') ?? $request->header('Accept-Language');
            $language = null;
            if ($langKey) {
                $language = CacheHelper::remember("language:code:{$langKey}", 86400, function () use ($langKey) {
                    return Language::where('code', $langKey)->orWhere('id', $langKey)->first();
                }, -1800, 1800);
            }

            if (!$language) {
                $language = CacheHelper::remember("language:default", 86400, function () {
                    return Language::where('code', 'en')->first() 
                        ?? Language::where('is_active', true)->first() 
                        ?? Language::first();
                }, -1800, 1800);
            }

            $langCode = strtolower($language?->code ?? 'en');
            $cacheKey = "founders_word:detail:{$id}:{$langCode}";

            $wordData = CacheHelper::remember($cacheKey, 86400, function () use ($id, $language, $langCode) {
                $word = FoundersWord::where('id', $id)
                    ->where('is_active', true)
                    ->first();

                if (!$word) {
                    return null;
                }

                return [
                    'id' => $word->id,
                    'language_id' => $language?->id ?? $word->language_id,
                    'language_code' => $langCode,
                    'language_name' => $language?->name ?? 'English',
                    'title' => $word->getTranslatedTitle($langCode),
                    'message' => $word->getTranslatedMessage($langCode),
                    'title_en' => $word->title_en ?: $word->title,
                    'message_en' => $word->message_en ?: $word->message,
                    'title_hi' => $word->title_hi,
                    'message_hi' => $word->message_hi,
                    'title_mr' => $word->title_mr,
                    'message_mr' => $word->message_mr,
                    'translations' => $word->translations ?: [
                        'en' => ['title' => $word->title_en ?: $word->title, 'message' => $word->message_en ?: $word->message],
                        'hi' => ['title' => $word->title_hi, 'message' => $word->message_hi],
                        'mr' => ['title' => $word->title_mr, 'message' => $word->message_mr],
                    ],
                    'image' => $word->image_url,
                    'image_path' => $word->image,
                    'is_active' => $word->is_active,
                    'created_at' => $word->created_at,
                    'updated_at' => $word->updated_at,
                ];
            }, -1800, 1800);

            if (!$wordData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Founder message not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'founders_word' => $wordData,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('FoundersWord show error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch founder message.'], 500);
        }
    }
}
