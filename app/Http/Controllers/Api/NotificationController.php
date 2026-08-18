<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationController extends Controller
{
    /**
     * Get target user ID from authenticated token or request query.
     */
    protected function resolveUserId(Request $request): ?int
    {
        if ($request->user()) {
            return (int) $request->user()->id;
        }

        $userId = $request->query('user_id') ?? $request->input('user_id');
        return $userId ? (int) $userId : null;
    }

    /**
     * Get unread and total notification counts.
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $total = AppNotification::where('user_id', $userId)->count();
            $unread = AppNotification::where('user_id', $userId)->where('is_read', false)->count();

            return ApiResponse::success([
                'total'  => $total,
                'unread' => $unread,
            ], 'Notification counts retrieved');

        } catch (Exception $e) {
            Log::error('Notification count error: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch notification count', 500);
        }
    }

    /**
     * Get paginated notifications list for in-app notification center.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $perPage = (int) ($request->query('per_page') ?? 20);
            $filter = $request->query('filter'); // 'read', 'unread', or null

            $query = AppNotification::where('user_id', $userId)->orderBy('created_at', 'desc');

            if ($filter === 'unread') {
                $query->where('is_read', false);
            } elseif ($filter === 'read') {
                $query->where('is_read', true);
            }

            $notifications = $query->paginate($perPage);

            return response()->json([
                'status'  => 'success',
                'message' => 'Notifications retrieved successfully',
                'data'    => [
                    'notifications' => $notifications->items(),
                    'pagination'    => [
                        'total'        => $notifications->total(),
                        'per_page'     => $notifications->perPage(),
                        'current_page' => $notifications->currentPage(),
                        'last_page'    => $notifications->lastPage(),
                        'has_more'     => $notifications->hasMorePages(),
                    ],
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification list error: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch notifications', 500);
        }
    }

    /**
     * Get details for a specific notification.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $notification = AppNotification::where('id', $id)->where('user_id', $userId)->first();
            if (!$notification) {
                return ApiResponse::error('Notification not found', 404);
            }

            return ApiResponse::success(['notification' => $notification], 'Notification retrieved');

        } catch (Exception $e) {
            Log::error('Notification show error: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch notification details', 500);
        }
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $notification = AppNotification::where('id', $id)->where('user_id', $userId)->first();
            if (!$notification) {
                return ApiResponse::error('Notification not found', 404);
            }

            $notification->is_read = true;
            $notification->save();

            return ApiResponse::success(['notification' => $notification], 'Notification marked as read');

        } catch (Exception $e) {
            Log::error('Notification markRead error: ' . $e->getMessage());
            return ApiResponse::error('Failed to update notification status', 500);
        }
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $updated = AppNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return ApiResponse::success([
                'updated_count' => $updated,
            ], 'All notifications marked as read');

        } catch (Exception $e) {
            Log::error('Notification markAllRead error: ' . $e->getMessage());
            return ApiResponse::error('Failed to mark all notifications as read', 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $notification = AppNotification::where('id', $id)->where('user_id', $userId)->first();
            if (!$notification) {
                return ApiResponse::error('Notification not found', 404);
            }

            $notification->delete();

            return ApiResponse::success(null, 'Notification deleted successfully');

        } catch (Exception $e) {
            Log::error('Notification destroy error: ' . $e->getMessage());
            return ApiResponse::error('Failed to delete notification', 500);
        }
    }
}
