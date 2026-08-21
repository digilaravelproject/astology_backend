<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    /**
     * List all active blogs.
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

            $cacheKey = $language ? "blogs:lang:{$language->id}" : "blogs:all_active";
            $blogs = \App\Helpers\CacheHelper::remember($cacheKey, 3600, function () use ($language) {
                $query = Blog::where('is_active', true);
                if ($language) {
                    $query->where('language_id', $language->id);
                }
                return $query->select(['id', 'language_id', 'title', 'subtitle', 'author', 'blog_image', 'blog_tags', 'type', 'is_active', 'created_at'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($blog) {
                        return [
                            'id' => $blog->id,
                            'language_id' => $blog->language_id,
                            'title' => $blog->title,
                            'subtitle' => $blog->subtitle,
                            'author' => $blog->author,
                            'type' => $blog->type,
                            'blog_image' => $blog->blog_image,
                            'blog_image_url' => $blog->blog_image_url,
                            'image' => $blog->blog_image_url,
                            'blog_tags' => $blog->blog_tags,
                            'is_active' => $blog->is_active,
                            'created_at' => $blog->created_at,
                        ];
                    });
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'blogs' => $blogs,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Blog index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch blogs.'], 500);
        }
    }

    /**
     * Get a single blog by ID.
     */
    public function show($id): JsonResponse
    {
        try {
            $blog = \App\Helpers\CacheHelper::remember("blog:detail:{$id}", 3600, function () use ($id) {
                return Blog::where('id', $id)
                    ->where('is_active', true)
                    ->first();
            });

            if (!$blog) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Blog not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'blog' => $blog,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Blog show error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch blog details.'], 500);
        }
    }

    /**
     * Search blogs (by query, type, tags)
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $langKey = $request->query('language') ?? $request->header('Accept-Language');
            $language = null;
            if ($langKey) {
                $language = \App\Models\Language::where('code', $langKey)->orWhere('id', $langKey)->first();
            }
            if (!$language) {
                $language = \App\Models\Language::where('is_active', true)->first() ?? \App\Models\Language::first();
            }

            $search = $request->query('q');
            $type = $request->query('type');
            $tags = $request->query('tags');

            $query = Blog::where('is_active', true);
            if ($language) {
                $query->where('language_id', $language->id);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('subtitle', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($tags) {
                $tagArray = is_string($tags) ? explode(',', $tags) : (array) $tags;
                foreach ($tagArray as $tagItem) {
                    $tagItem = trim($tagItem);
                    if ($tagItem !== '') {
                        $query->whereJsonContains('blog_tags', $tagItem);
                    }
                }
            }

            $blogs = $query->orderByDesc('created_at')->get()
                ->map(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'language_id' => $blog->language_id,
                        'title' => $blog->title,
                        'subtitle' => $blog->subtitle,
                        'author' => $blog->author,
                        'type' => $blog->type,
                        'blog_image' => $blog->blog_image,
                        'blog_image_url' => $blog->blog_image_url,
                        'image' => $blog->blog_image_url,
                        'blog_tags' => $blog->blog_tags,
                        'is_active' => $blog->is_active,
                        'created_at' => $blog->created_at,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'blogs' => $blogs,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Blog search error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to search blogs.'], 500);
        }
    }
}
