<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $query = AppNotification::with('user')->latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
        }

        // Filter by read status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'read') {
                $query->where('is_read', true);
            } elseif ($status === 'unread') {
                $query->where('is_read', false);
            }
        }

        $notifications = $query->paginate(20)->appends($request->all());

        return view('admin.app_notifications.index', compact('notifications'));
    }

    /**
     * Display the specified notification.
     */
    public function show($id)
    {
        $notification = AppNotification::with('user')->findOrFail($id);

        // Mark as read when viewing
        if (!$notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return view('admin.app_notifications.show', compact('notification'));
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return redirect()->back()
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->update(['is_read' => false]);

        return redirect()->back()
            ->with('success', 'Notification marked as unread.');
    }

    /**
     * Delete the specified notification.
     */
    public function destroy($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->delete();

        return redirect()->route('admin.app-notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Bulk delete read notifications.
     */
    public function bulkDeleteRead()
    {
        AppNotification::where('is_read', true)->delete();

        return redirect()->route('admin.app-notifications.index')
            ->with('success', 'All read notifications deleted successfully.');
    }
}
