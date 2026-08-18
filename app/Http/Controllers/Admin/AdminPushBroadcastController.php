<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Exception;

class AdminPushBroadcastController extends Controller
{
    /**
     * Display broadcast campaigns history.
     */
    public function index(Request $request): View
    {
        $query = BroadcastNotification::with(['creator', 'targetUser'])->latest();

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $broadcasts = $query->paginate(15)->appends($request->all());

        $stats = [
            'total_campaigns' => BroadcastNotification::count(),
            'total_delivered' => BroadcastNotification::sum('successful_count'),
            'total_failed'    => BroadcastNotification::sum('failed_count'),
        ];

        $campaigns = $broadcasts;

        return view('admin.push_notifications.index', compact('broadcasts', 'campaigns', 'stats'));
    }


    /**
     * Show form to compose a new push notification broadcast.
     */
    public function create(): View
    {
        return view('admin.push_notifications.create');
    }

    /**
     * Store and queue the broadcast push notification campaign.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'          => 'required|string|max:191',
            'body'           => 'required|string|max:1000',
            'target_type'    => 'required|in:all,users,astrologers,single_user',
            'target_user_id' => 'required_if:target_type,single_user|nullable|exists:users,id',
            'image_url'      => 'nullable|url|max:500',
            'click_action'   => 'nullable|string|max:191',
            'custom_data'    => 'nullable|string', // JSON string from form
        ]);

        try {
            $customData = [];
            if ($request->filled('custom_data')) {
                $decoded = json_decode($request->input('custom_data'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $customData = $decoded;
                }
            }

            $broadcast = BroadcastNotification::create([
                'title'          => $request->input('title'),
                'body'           => $request->input('body'),
                'image_url'      => $request->input('image_url'),
                'target_type'    => $request->input('target_type'),
                'target_user_id' => $request->input('target_type') === 'single_user' ? $request->input('target_user_id') : null,
                'click_action'   => $request->input('click_action') ?? 'FLUTTER_NOTIFICATION_CLICK',
                'data_payload'   => $customData,
                'status'         => 'pending',
                'created_by'     => Auth::guard('admin')->id(),
            ]);

            // Dispatch asynchronous delivery
            NotificationService::sendBroadcast($broadcast);

            return redirect()->route('admin.push-notifications.index')
                ->with('success', 'Push notification broadcast queued for delivery successfully.');

        } catch (Exception $e) {
            Log::error('Broadcast store error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to dispatch broadcast: ' . $e->getMessage());
        }
    }

    /**
     * Live Ajax search for single-user selection.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'phone', 'email', 'user_type')
            ->limit(15)
            ->get();

        return response()->json($users);
    }

    /**
     * Delete broadcast record.
     */
    public function destroy($id): RedirectResponse
    {
        $broadcast = BroadcastNotification::findOrFail($id);
        $broadcast->delete();

        return redirect()->back()->with('success', 'Broadcast campaign record deleted.');
    }
}
