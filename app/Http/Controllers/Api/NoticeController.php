<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoticeController extends Controller
{
    /**
     * List notices, optionally filtering by tag.
     */
    public function index(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Notice index error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch notices.'], 500);
        }
    }
}
