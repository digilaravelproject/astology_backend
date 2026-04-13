<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoundersWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FounderWordsController extends Controller
{
    public function index(Request $request)
    {
        $query = FoundersWord::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $words = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => FoundersWord::count(),
            'active' => FoundersWord::where('is_active', true)->count(),
            'inactive' => FoundersWord::where('is_active', false)->count(),
        ];

        return view('admin.founder_words.index', compact('words', 'stats'));
    }

    public function create()
    {
        $word = new FoundersWord();
        return view('admin.founder_words.create', compact('word'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('founder_words', 'public');
        }

        FoundersWord::create($data);

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word created successfully.');
    }

    public function edit($id)
    {
        $word = FoundersWord::findOrFail($id);
        return view('admin.founder_words.create', compact('word'));
    }

    public function update(Request $request, $id)
    {
        $word = FoundersWord::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($word->image && Storage::disk('public')->exists($word->image)) {
                Storage::disk('public')->delete($word->image);
            }
            $data['image'] = $request->file('image')->store('founder_words', 'public');
        }

        $word->update($data);

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word updated successfully.');
    }

    public function destroy($id)
    {
        $word = FoundersWord::findOrFail($id);
        
        // Delete image if exists
        if ($word->image && Storage::disk('public')->exists($word->image)) {
            Storage::disk('public')->delete($word->image);
        }
        
        $word->delete();

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word deleted successfully.');
    }
}
