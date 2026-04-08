<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingVideoController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingVideo::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $videos = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        $types = TrainingVideo::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->filter()
            ->values();

        $stats = [
            'total' => TrainingVideo::count(),
            'active' => TrainingVideo::where('is_active', true)->count(),
            'inactive' => TrainingVideo::where('is_active', false)->count(),
            'types' => TrainingVideo::count(),
        ];

        return view('admin.training_videos.index', compact('videos', 'types', 'stats'));
    }

    public function create()
    {
        $video = new TrainingVideo();
        $types = TrainingVideo::query()
            ->select('type')
            ->distinct()
            ->whereNotNull('type')
            ->pluck('type')
            ->filter()
            ->values();

        return view('admin.training_videos.create', compact('video', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'video_file' => 'required|file|mimes:mp4,webm,mov,avi|max:51200',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        if ($request->hasFile('video_file')) {
            $data['video_url'] = $this->storeFile($request->file('video_file'), 'training_videos/videos');
        }

        if ($request->hasFile('thumbnail_file')) {
            $data['thumbnail_url'] = $this->storeFile($request->file('thumbnail_file'), 'training_videos/thumbnails');
        }

        TrainingVideo::create($data);

        return redirect()->route('admin.training_videos.index')->with('success', 'Training video created successfully.');
    }

    public function edit($id)
    {
        $video = TrainingVideo::findOrFail($id);
        $types = TrainingVideo::query()
            ->select('type')
            ->distinct()
            ->whereNotNull('type')
            ->pluck('type')
            ->filter()
            ->values();

        return view('admin.training_videos.create', compact('video', 'types'));
    }

    public function update(Request $request, $id)
    {
        $video = TrainingVideo::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov,avi|max:51200',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        if ($request->hasFile('video_file')) {
            $this->deleteStoragePath($video->video_url);
            $data['video_url'] = $this->storeFile($request->file('video_file'), 'training_videos/videos');
        }

        if ($request->hasFile('thumbnail_file')) {
            $this->deleteStoragePath($video->thumbnail_url);
            $data['thumbnail_url'] = $this->storeFile($request->file('thumbnail_file'), 'training_videos/thumbnails');
        }

        $video->update($data);

        return redirect()->route('admin.training_videos.index')->with('success', 'Training video updated successfully.');
    }

    public function destroy($id)
    {
        $video = TrainingVideo::findOrFail($id);
        $video->delete();

        return redirect()->route('admin.training_videos.index')->with('success', 'Training video deleted successfully.');
    }

    private function storeFile($file, $directory)
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');
        return '/storage/' . $path;
    }

    private function deleteStoragePath($path)
    {
        if (! $path) {
            return;
        }

        $trimmed = preg_replace('#^/storage/#', '', $path);
        if (Storage::disk('public')->exists($trimmed)) {
            Storage::disk('public')->delete($trimmed);
        }
    }
}
