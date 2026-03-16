<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * List notices, optionally filtering by tag.
     *
     * Query params:
     * - tag: filter by tag (case-insensitive)
     * - is_urgent: filter by urgent notices (1/0)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notice::query()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        if ($tag = $request->query('tag')) {
            $query->whereRaw('LOWER(tag) = ?', [strtolower(trim($tag))]);
        }

        if (! is_null($request->query('is_urgent'))) {
            $query->where('is_urgent', boolval($request->query('is_urgent')));
        }

        $notices = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'notices' => $notices,
            ],
        ], 200);
    }
}
