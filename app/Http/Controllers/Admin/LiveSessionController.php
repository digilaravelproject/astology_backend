<?php

namespace App\Http\Controllers\Admin;

use App\Models\LiveSession;
use App\Models\Astrologer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LiveSessionController extends Controller
{
    /**
     * Display a listing of live sessions
     */
    public function index(Request $request)
    {
        $query = LiveSession::with('astrologer.user');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('astrologer.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Astrologer filter
        if ($request->filled('astrologer_id')) {
            $query->where('astrologer_id', $request->get('astrologer_id'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Session type filter
        if ($request->filled('session_type')) {
            $query->where('session_type', $request->get('session_type'));
        }

        $liveSessions = $query->orderBy('scheduled_at', 'desc')->paginate(15);
        $astrologers = Astrologer::with('user')->get();

        return view('admin.astrologers.live-sessions.index', compact('liveSessions', 'astrologers'));
    }

    /**
     * Display a specific live session
     */
    public function show($id)
    {
        $liveSession = LiveSession::with('astrologer.user')->findOrFail($id);
        return view('admin.astrologers.live-sessions.show', compact('liveSession'));
    }

    /**
     * Update live session status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        $liveSession = LiveSession::findOrFail($id);
        $liveSession->update(['status' => $request->status]);

        return redirect()->route('admin.astrologers.live-sessions.show', $liveSession->id)
                        ->with('success', 'Live session status updated successfully!');
    }

    /**
     * Delete a live session
     */
    public function destroy($id)
    {
        $liveSession = LiveSession::findOrFail($id);
        $title = $liveSession->title;
        $liveSession->delete();

        return redirect()->route('admin.astrologers.live-sessions.index')
                        ->with('success', "Live session '{$title}' deleted successfully!");
    }
}
