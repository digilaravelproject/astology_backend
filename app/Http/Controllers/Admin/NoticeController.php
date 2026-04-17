<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * Display a listing of notices.
     */
    public function index(Request $request)
    {
        $query = Notice::latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by urgency
        if ($request->filled('urgency')) {
            $urgency = $request->input('urgency');
            if ($urgency === 'urgent') {
                $query->where('is_urgent', true);
            } elseif ($urgency === 'normal') {
                $query->where('is_urgent', false);
            }
        }

        $notices = $query->paginate(20)->appends($request->all());

        return view('admin.notices.index', compact('notices'));
    }

    /**
     * Show the form for creating a new notice.
     */
    public function create()
    {
        $tags = ['announcement', 'maintenance', 'feature', 'security', 'policy', 'update'];
        $icons = ['info-circle', 'bell', 'star', 'warning', 'check-circle', 'exclamation-triangle'];

        return view('admin.notices.form', compact('tags', 'icons'));
    }

    /**
     * Store a newly created notice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'tag' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'is_urgent' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        Notice::create($validated);

        return redirect()->route('admin.notices.index')
            ->with('success', 'Notice created successfully.');
    }

    /**
     * Display the specified notice.
     */
    public function show($id)
    {
        $notice = Notice::findOrFail($id);

        return view('admin.notices.show', compact('notice'));
    }

    /**
     * Show the form for editing the notice.
     */
    public function edit($id)
    {
        $notice = Notice::findOrFail($id);
        $tags = ['announcement', 'maintenance', 'feature', 'security', 'policy', 'update'];
        $icons = ['info-circle', 'bell', 'star', 'warning', 'check-circle', 'exclamation-triangle'];

        return view('admin.notices.form', compact('notice', 'tags', 'icons'));
    }

    /**
     * Update the specified notice.
     */
    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'tag' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'is_urgent' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $notice->update($validated);

        return redirect()->route('admin.notices.show', $notice->id)
            ->with('success', 'Notice updated successfully.');
    }

    /**
     * Toggle notice status.
     */
    public function toggleStatus($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->update(['is_active' => !$notice->is_active]);

        $status = $notice->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.notices.show', $notice->id)
            ->with('success', "Notice {$status} successfully.");
    }

    /**
     * Delete the specified notice.
     */
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return redirect()->route('admin.notices.index')
            ->with('success', 'Notice deleted successfully.');
    }
}
