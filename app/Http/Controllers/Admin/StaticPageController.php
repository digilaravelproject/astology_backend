<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaticPageController extends Controller
{
    public function index(Request $request)
    {
        $pages = StaticPage::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $pages->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $pages->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $pages->where('is_active', true);
            } elseif ($status === 'inactive') {
                $pages->where('is_active', false);
            }
        }

        $pages = $pages->orderByDesc('created_at')->paginate(15)->withQueryString();

        $types = StaticPage::getTypes();
        $total = StaticPage::count();
        $active = StaticPage::where('is_active', true)->count();
        $inactive = StaticPage::where('is_active', false)->count();

        return view('admin.static_pages.index', compact('pages', 'types', 'total', 'active', 'inactive'));
    }

    public function create()
    {
        $types = StaticPage::getTypes();
        $page = new StaticPage;

        return view('admin.static_pages.create', compact('page', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([...array_keys(StaticPage::getTypes()), 'other'])],
            'custom_type' => ['nullable', 'required_if:type,other', 'string', 'max:100'],
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['type'] = $data['type'] === 'other'
            ? Str::slug($data['custom_type'], '_')
            : $data['type'];

        if ($data['type'] === '') {
            return back()->withErrors(['custom_type' => 'Please enter a valid page type.'])->withInput();
        }

        if (StaticPage::where('type', $data['type'])->exists()) {
            $field = $request->input('type') === 'other' ? 'custom_type' : 'type';

            return back()->withErrors([$field => 'This page type already exists.'])->withInput();
        }

        unset($data['custom_type']);

        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        StaticPage::create($data);

        return redirect()->route('admin.static_pages.index')->with('success', 'Static page created successfully.');
    }

    public function edit($id)
    {
        $page = StaticPage::findOrFail($id);
        $types = StaticPage::getTypes();

        return view('admin.static_pages.create', compact('page', 'types'));
    }

    public function update(Request $request, $id)
    {
        $page = StaticPage::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        $page->update($data);

        return redirect()->route('admin.static_pages.index')->with('success', 'Static page updated successfully.');
    }

    public function destroy($id)
    {
        $page = StaticPage::findOrFail($id);
        $page->delete();

        return redirect()->route('admin.static_pages.index')->with('success', 'Static page deleted successfully.');
    }

    public function show($id)
    {
        $page = StaticPage::findOrFail($id);

        return view('admin.static_pages.show', compact('page'));
    }
}
