<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;

class BlogController extends Controller
{
    /**
     * List all active blogs.
     */
    public function index(): JsonResponse
    {
        $blogs = Blog::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'blogs' => $blogs,
            ],
        ], 200);
    }

    /**
     * Get a single blog by ID.
     */
    public function show($id): JsonResponse
    {
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
    }
}
