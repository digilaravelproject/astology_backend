<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoundersWord;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FounderWordsController extends Controller
{
    public function index(Request $request)
    {
        $query = FoundersWord::with('language');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('title_en', 'like', "%{$search}%")
                  ->orWhere('message_en', 'like', "%{$search}%")
                  ->orWhere('title_hi', 'like', "%{$search}%")
                  ->orWhere('message_hi', 'like', "%{$search}%")
                  ->orWhere('title_mr', 'like', "%{$search}%")
                  ->orWhere('message_mr', 'like', "%{$search}%");
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

        $languages = Language::where('is_active', true)->get();

        return view('admin.founder_words.index', compact('words', 'stats', 'languages'));
    }

    public function create()
    {
        $word = new FoundersWord();
        $languages = Language::where('is_active', true)->get();
        return view('admin.founder_words.create', compact('word', 'languages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_en' => 'nullable|string|max:255',
            'message_en' => 'nullable|string|max:2000',
            'title_hi' => 'nullable|string|max:255',
            'message_hi' => 'nullable|string|max:2000',
            'title_mr' => 'nullable|string|max:255',
            'message_mr' => 'nullable|string|max:2000',
            // Fallback fields
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
            'language_id' => 'nullable|exists:languages,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        // Require at least one language title and message
        if (empty($data['title_en']) && empty($data['title_hi']) && empty($data['title_mr']) && empty($data['title'])) {
            return back()->withErrors(['title_en' => 'Please provide a title in at least one language (English).'])->withInput();
        }

        if (empty($data['message_en']) && empty($data['message_hi']) && empty($data['message_mr']) && empty($data['message'])) {
            return back()->withErrors(['message_en' => 'Please provide a message in at least one language (English).'])->withInput();
        }

        // Set primary title and message
        $data['title'] = $data['title_en'] ?: ($data['title_hi'] ?: ($data['title_mr'] ?: ($data['title'] ?? '')));
        $data['message'] = $data['message_en'] ?: ($data['message_hi'] ?: ($data['message_mr'] ?: ($data['message'] ?? '')));

        // If title_en or message_en empty but title/message provided, backfill en
        if (empty($data['title_en'])) {
            $data['title_en'] = $data['title'];
        }
        if (empty($data['message_en'])) {
            $data['message_en'] = $data['message'];
        }

        // Prepare translations json
        $data['translations'] = [
            'en' => [
                'title' => $data['title_en'] ?? '',
                'message' => $data['message_en'] ?? '',
            ],
            'hi' => [
                'title' => $data['title_hi'] ?? '',
                'message' => $data['message_hi'] ?? '',
            ],
            'mr' => [
                'title' => $data['title_mr'] ?? '',
                'message' => $data['message_mr'] ?? '',
            ],
        ];

        // Default language
        if (empty($data['language_id'])) {
            $defaultLang = Language::where('code', 'en')->first() ?? Language::first();
            $data['language_id'] = $defaultLang?->id;
        }

        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('founder_words', 'public');
        }

        FoundersWord::create($data);

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word created successfully with multi-language support.');
    }

    public function edit($id)
    {
        $word = FoundersWord::findOrFail($id);
        $languages = Language::where('is_active', true)->get();
        return view('admin.founder_words.create', compact('word', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $word = FoundersWord::findOrFail($id);

        $data = $request->validate([
            'title_en' => 'nullable|string|max:255',
            'message_en' => 'nullable|string|max:2000',
            'title_hi' => 'nullable|string|max:255',
            'message_hi' => 'nullable|string|max:2000',
            'title_mr' => 'nullable|string|max:255',
            'message_mr' => 'nullable|string|max:2000',
            // Fallback fields
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
            'language_id' => 'nullable|exists:languages,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        // Require at least one language title and message
        if (empty($data['title_en']) && empty($data['title_hi']) && empty($data['title_mr']) && empty($data['title'])) {
            return back()->withErrors(['title_en' => 'Please provide a title in at least one language (English).'])->withInput();
        }

        if (empty($data['message_en']) && empty($data['message_hi']) && empty($data['message_mr']) && empty($data['message'])) {
            return back()->withErrors(['message_en' => 'Please provide a message in at least one language (English).'])->withInput();
        }

        // Set primary title and message
        $data['title'] = $data['title_en'] ?: ($data['title_hi'] ?: ($data['title_mr'] ?: ($data['title'] ?? '')));
        $data['message'] = $data['message_en'] ?: ($data['message_hi'] ?: ($data['message_mr'] ?: ($data['message'] ?? '')));

        if (empty($data['title_en'])) {
            $data['title_en'] = $data['title'];
        }
        if (empty($data['message_en'])) {
            $data['message_en'] = $data['message'];
        }

        // Prepare translations json
        $data['translations'] = [
            'en' => [
                'title' => $data['title_en'] ?? '',
                'message' => $data['message_en'] ?? '',
            ],
            'hi' => [
                'title' => $data['title_hi'] ?? '',
                'message' => $data['message_hi'] ?? '',
            ],
            'mr' => [
                'title' => $data['title_mr'] ?? '',
                'message' => $data['message_mr'] ?? '',
            ],
        ];

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

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word updated successfully with multi-language support.');
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
