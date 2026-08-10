<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        $query = Language::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $languages = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $total = Language::count();
        $active = Language::where('is_active', true)->count();
        $inactive = Language::where('is_active', false)->count();

        return view('admin.languages.index', compact('languages', 'total', 'active', 'inactive'));
    }

    public function create()
    {
        return view('admin.languages.form', ['language' => new Language()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:languages,code',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        Language::create($data);

        return redirect()->route('admin.languages.index')->with('success', 'Language added successfully.');
    }

    public function edit($id)
    {
        $language = Language::findOrFail($id);
        return view('admin.languages.form', compact('language'));
    }

    public function update(Request $request, $id)
    {
        $language = Language::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:languages,code,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $language->update($data);

        return redirect()->route('admin.languages.index')->with('success', 'Language updated successfully.');
    }

    public function destroy($id)
    {
        $language = Language::findOrFail($id);
        
        // Optionally prevent deleting languages that are in use or handle Cascade
        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', 'Language deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $language = Language::findOrFail($id);
        $language->is_active = !$language->is_active;
        $language->save();

        return redirect()->route('admin.languages.index')->with('success', 'Language status updated successfully.');
    }
}
