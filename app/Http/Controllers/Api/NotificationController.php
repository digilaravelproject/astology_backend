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

            return response()->json([
                'status'  => 'success',
                'message' => 'Notification counts retrieved',
                'data'    => [
                    'total'        => (int) $total,
                    'unread'       => (int) $unread,
                    'total_count'  => (int) $total,
                    'unread_count' => (int) $unread,
                    'count'        => (int) $unread,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification count error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch notification count',
            ], 500);
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
                return response()->json(['status' => 'error', 'message' => 'Notification not found.'], 404);
            }

            $notification->is_read = true;
            $notification->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Notification marked as read',
                'data'    => [
                    'notification' => $notification,
                    'unread_count' => (int) AppNotification::where('user_id', $userId)->where('is_read', false)->count(),
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification markRead error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update notification status'], 500);
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

            return response()->json([
                'status'  => 'success',
                'message' => 'All notifications marked as read',
                'data'    => [
                    'updated_count' => $updated,
                    'unread_count'  => 0,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification markAllRead error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to mark all notifications as read'], 500);
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
                return response()->json(['status' => 'error', 'message' => 'Notification not found.'], 404);
            }

            $notification->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Notification deleted successfully',
                'data'    => [
                    'id'           => (int) $id,
                    'deleted'      => true,
                    'unread_count' => (int) AppNotification::where('user_id', $userId)->where('is_read', false)->count(),
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification destroy error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete notification'], 500);
        }
    }

    /**
     * Delete all notifications for the authenticated user/astrologer.
     */
    public function deleteAll(Request $request): JsonResponse
    {
        try {
            $userId = $this->resolveUserId($request);
            if (!$userId) {
                return ApiResponse::error('User identification required', 400);
            }

            $deleted = AppNotification::where('user_id', $userId)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'All notifications deleted successfully',
                'data'    => [
                    'deleted_count' => $deleted,
                    'unread_count'  => 0,
                    'total_count'   => 0,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Notification deleteAll error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete all notifications'], 500);
        }
    }
}
