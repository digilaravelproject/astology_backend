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
                  ->orWhere('message_mr', 'like', "%{$search}%")
                  ->orWhere('translations', 'like', "%{$search}%");
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
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
            'translations' => 'nullable|array',
            'title_en' => 'nullable|string|max:255',
            'message_en' => 'nullable|string|max:2000',
        ]);

        $translationsInput = $request->input('translations', []);

        // Also merge any direct inputs (title_en, title_hi, title_mr, etc.) into translations
        foreach (['en', 'hi', 'mr', 'gu', 'bn', 'ta', 'te', 'kn', 'ml', 'pa', 'or', 'ur'] as $code) {
            if ($request->filled('title_' . $code) || $request->filled('message_' . $code)) {
                $translationsInput[$code] = [
                    'title' => $request->input('title_' . $code, $translationsInput[$code]['title'] ?? ''),
                    'message' => $request->input('message_' . $code, $translationsInput[$code]['message'] ?? ''),
                ];
            }
        }

        // English is the default anchor
        $titleEn = $translationsInput['en']['title'] ?? ($request->input('title_en') ?? ($request->input('title') ?? ''));
        $messageEn = $translationsInput['en']['message'] ?? ($request->input('message_en') ?? ($request->input('message') ?? ''));

        // If English is empty, try to find any first filled language
        if (empty($titleEn) || empty($messageEn)) {
            foreach ($translationsInput as $t) {
                if (empty($titleEn) && !empty($t['title'])) $titleEn = $t['title'];
                if (empty($messageEn) && !empty($t['message'])) $messageEn = $t['message'];
            }
        }

        if (empty($titleEn) || empty($messageEn)) {
            return back()->withErrors(['title_en' => 'Please provide at least an English title and message.'])->withInput();
        }

        $data = [
            'title' => $titleEn,
            'message' => $messageEn,
            'title_en' => $titleEn,
            'message_en' => $messageEn,
            'title_hi' => $translationsInput['hi']['title'] ?? null,
            'message_hi' => $translationsInput['hi']['message'] ?? null,
            'title_mr' => $translationsInput['mr']['title'] ?? null,
            'message_mr' => $translationsInput['mr']['message'] ?? null,
            'translations' => $translationsInput,
            'is_active' => $request->has('is_active'),
        ];

        // Default language id
        $defaultLang = Language::where('code', 'en')->first() ?? Language::first();
        $data['language_id'] = $defaultLang?->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('founder_words', 'public');
        }

        FoundersWord::create($data);

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word created successfully with all language translations.');
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

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
            'translations' => 'nullable|array',
            'title_en' => 'nullable|string|max:255',
            'message_en' => 'nullable|string|max:2000',
        ]);

        $translationsInput = $request->input('translations', []);

        // Merge any direct inputs
        foreach (['en', 'hi', 'mr', 'gu', 'bn', 'ta', 'te', 'kn', 'ml', 'pa', 'or', 'ur'] as $code) {
            if ($request->filled('title_' . $code) || $request->filled('message_' . $code)) {
                $translationsInput[$code] = [
                    'title' => $request->input('title_' . $code, $translationsInput[$code]['title'] ?? ''),
                    'message' => $request->input('message_' . $code, $translationsInput[$code]['message'] ?? ''),
                ];
            }
        }

        // English anchor
        $titleEn = $translationsInput['en']['title'] ?? ($request->input('title_en') ?? ($request->input('title') ?? ''));
        $messageEn = $translationsInput['en']['message'] ?? ($request->input('message_en') ?? ($request->input('message') ?? ''));

        if (empty($titleEn) || empty($messageEn)) {
            foreach ($translationsInput as $t) {
                if (empty($titleEn) && !empty($t['title'])) $titleEn = $t['title'];
                if (empty($messageEn) && !empty($t['message'])) $messageEn = $t['message'];
            }
        }

        if (empty($titleEn) || empty($messageEn)) {
            return back()->withErrors(['title_en' => 'Please provide at least an English title and message.'])->withInput();
        }

        $data = [
            'title' => $titleEn,
            'message' => $messageEn,
            'title_en' => $titleEn,
            'message_en' => $messageEn,
            'title_hi' => $translationsInput['hi']['title'] ?? null,
            'message_hi' => $translationsInput['hi']['message'] ?? null,
            'title_mr' => $translationsInput['mr']['title'] ?? null,
            'message_mr' => $translationsInput['mr']['message'] ?? null,
            'translations' => $translationsInput,
            'is_active' => $request->has('is_active'),
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($word->image && Storage::disk('public')->exists($word->image)) {
                Storage::disk('public')->delete($word->image);
            }
            $data['image'] = $request->file('image')->store('founder_words', 'public');
        }

        $word->update($data);

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word updated successfully with all language translations.');
    }

    public function destroy($id)
    {
        $word = FoundersWord::findOrFail($id);
        
        if ($word->image && Storage::disk('public')->exists($word->image)) {
            Storage::disk('public')->delete($word->image);
        }
        
        $word->delete();

        return redirect()->route('admin.founder_words.index')->with('success', 'Founder word deleted successfully.');
    }
}
