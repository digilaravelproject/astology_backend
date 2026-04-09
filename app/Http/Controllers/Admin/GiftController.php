<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Gift;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function index(Request $request)
    {
        $query = Gift::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $gifts = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        $stats = [
            'total' => Gift::count(),
            'active' => Gift::where('is_active', true)->count(),
            'inactive' => Gift::where('is_active', false)->count(),
        ];

        return view('admin.gifts.index', compact('gifts', 'stats'));
    }

    public function create()
    {
        $gift = new Gift();
        return view('admin.gifts.form', compact('gift'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'icon_file' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('icon_file')) {
            $data['icon_url'] = $this->storeFile($request->file('icon_file'), 'gifts/icons');
        }

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        Gift::create($data);

        return redirect()->route('admin.gifts.index')->with('success', 'Gift created successfully.');
    }

    public function edit($id)
    {
        $gift = Gift::findOrFail($id);
        return view('admin.gifts.form', compact('gift'));
    }

    public function update(Request $request, $id)
    {
        $gift = Gift::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'icon_file' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('icon_file')) {
            $this->deleteStoragePath($gift->icon_url);
            $data['icon_url'] = $this->storeFile($request->file('icon_file'), 'gifts/icons');
        }

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        $gift->update($data);

        return redirect()->route('admin.gifts.index')->with('success', 'Gift updated successfully.');
    }

    public function destroy($id)
    {
        $gift = Gift::findOrFail($id);
        $this->deleteStoragePath($gift->icon_url);
        $gift->delete();

        return redirect()->route('admin.gifts.index')->with('success', 'Gift deleted successfully.');
    }

    private function storeFile($file, $directory)
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');
        return '/storage/' . $path;
    }

    private function deleteStoragePath($path)
    {
        if (!$path) {
            return;
        }

        $trimmed = preg_replace('#^/storage/#', '', $path);
        if (Storage::disk('public')->exists($trimmed)) {
            Storage::disk('public')->delete($trimmed);
        }
    }
}
