<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AstrologerGallery;
use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AstrologerGalleryController extends Controller
{
    /**
     * Display gallery images for all astrologers
     */
    public function index(Request $request)
    {
        $query = AstrologerGallery::query()->with(['astrologer.user']);

        if ($request->filled('astrologer_id')) {
            $query->where('astrologer_id', $request->input('astrologer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('astrologer.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $galleries = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $astrologers = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->orderBy('name')
            ->get();

        return view('admin.astrologers.gallery.index', compact('galleries', 'astrologers'));
    }

    /**
     * Show gallery images for a specific astrologer
     */
    public function show($astrologerId)
    {
        $astrologer = Astrologer::with('user')->findOrFail($astrologerId);
        $galleries = AstrologerGallery::where('astrologer_id', $astrologerId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.astrologers.gallery.show', compact('astrologer', 'galleries'));
    }

    /**
     * Approve gallery image
     */
    public function approve($id)
    {
        try {
            $gallery = AstrologerGallery::findOrFail($id);
            $gallery->update([
                'status' => 'active',
                'remarks' => null,
            ]);

            return redirect()->back()->with('success', 'Gallery image approved successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve image: ' . $e->getMessage());
        }
    }

    /**
     * Disapprove gallery image with remarks
     */
    public function disapprove(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'remarks' => 'required|string|max:500',
            ]);

            $gallery = AstrologerGallery::findOrFail($id);
            $gallery->update([
                'status' => 'pending',
                'remarks' => $validated['remarks'],
            ]);

            return redirect()->back()->with('success', 'Gallery image marked as pending with remarks');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to disapprove image: ' . $e->getMessage());
        }
    }

    /**
     * Delete gallery image
     */
    public function destroy($id)
    {
        try {
            $gallery = AstrologerGallery::findOrFail($id);

            // Delete from storage
            if (Storage::disk('public')->exists($gallery->image_path)) {
                Storage::disk('public')->delete($gallery->image_path);
            }

            $gallery->delete();

            return redirect()->back()->with('success', 'Gallery image deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete image: ' . $e->getMessage());
        }
    }
}
