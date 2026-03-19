<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Remedy;
use Illuminate\Http\Request;

class RemedyController extends Controller
{
    public function index(Request $request)
    {
        $query = Remedy::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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

        $remedies = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $total = Remedy::count();
        $active = Remedy::where('is_active', true)->count();
        $inactive = Remedy::where('is_active', false)->count();

        return view('admin.remedies.index', compact('remedies', 'total', 'active', 'inactive'));
    }

    public function create()
    {
        return view('admin.remedies.form', ['remedy' => new Remedy()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        Remedy::create($data);

        return redirect()->route('admin.remedies.index')->with('success', 'Remedy added successfully.');
    }

    public function edit($id)
    {
        $remedy = Remedy::findOrFail($id);
        return view('admin.remedies.form', compact('remedy'));
    }

    public function update(Request $request, $id)
    {
        $remedy = Remedy::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $remedy->update($data);

        return redirect()->route('admin.remedies.index')->with('success', 'Remedy updated successfully.');
    }

    public function destroy($id)
    {
        $remedy = Remedy::findOrFail($id);
        $remedy->delete();

        return redirect()->route('admin.remedies.index')->with('success', 'Remedy deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $remedy = Remedy::findOrFail($id);
        $remedy->is_active = !$remedy->is_active;
        $remedy->save();

        return redirect()->route('admin.remedies.index')->with('success', 'Remedy status updated successfully.');
    }
}
