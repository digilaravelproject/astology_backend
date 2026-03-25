<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function count(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $total = AppNotification::where('user_id', $userId)->count();
        $unread = AppNotification::where('user_id', $userId)->where('is_read', false)->count();

        return response()->json(['status' => 'success', 'data' => ['total' => $total, 'unread' => $unread]], 200);
    }

    public function list(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $notifications = AppNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => ['notifications' => $notifications]], 200);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required'], 400);
        }

        $notification = AppNotification::where('id', $id)->where('user_id', $userId)->first();
        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => ['notification' => $notification]], 200);
    }

    public function markRead(Request $request, $id)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required'], 400);
        }

        $notification = AppNotification::where('id', $id)->where('user_id', $userId)->first();
        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification not found'], 404);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json(['status' => 'success', 'message' => 'Notification marked as read', 'data' => ['notification' => $notification]], 200);
    }
}
