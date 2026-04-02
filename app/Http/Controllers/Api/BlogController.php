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
    public function index(): JsonResponse
    {
        try {
            $blogs = Blog::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();

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
            $blog = Blog::where('id', $id)
                ->where('is_active', true)
                ->first();

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
            $search = $request->query('q');
            $type = $request->query('type');
            $tags = $request->query('tags');

            $query = Blog::where('is_active', true);

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

            $blogs = $query->orderByDesc('created_at')->get();

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
