<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kundli;
use App\Models\User;
use Illuminate\Http\Request;

class KundliController extends Controller
{
    /**
     * Display a listing of kundlis.
     */
    public function index(Request $request)
    {
        $query = Kundli::latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $gender = $request->input('gender');
            $query->where('gender', $gender);
        }

        $kundlis = $query->paginate(20)->appends($request->all());

        return view('admin.kundlis.index', compact('kundlis'));
    }

    /**
     * Show the form for creating a new kundli.
     */
    public function create()
    {
        return view('admin.kundlis.form');
    }

    /**
     * Store a newly created kundli.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'birth_date' => 'required|date',
            'birth_time' => 'required|date_format:H:i',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'datetime' => 'nullable|date_time',
        ]);

        Kundli::create($validated);

        return redirect()->route('admin.kundlis.index')
            ->with('success', 'Kundli created successfully.');
    }

    /**
     * Display the specified kundli.
     */
    public function show($id)
    {
        $kundli = Kundli::findOrFail($id);

        return view('admin.kundlis.show', compact('kundli'));
    }

    /**
     * Show the form for editing the kundli.
     */
    public function edit($id)
    {
        $kundli = Kundli::findOrFail($id);

        return view('admin.kundlis.form', compact('kundli'));
    }

    /**
     * Update the specified kundli.
     */
    public function update(Request $request, $id)
    {
        $kundli = Kundli::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'birth_date' => 'required|date',
            'birth_time' => 'required|date_format:H:i',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'datetime' => 'nullable|date_time',
        ]);

        $kundli->update($validated);

        return redirect()->route('admin.kundlis.show', $kundli->id)
            ->with('success', 'Kundli updated successfully.');
    }

    /**
     * Delete the specified kundli.
     */
    public function destroy($id)
    {
        $kundli = Kundli::findOrFail($id);
        $kundli->delete();

        return redirect()->route('admin.kundlis.index')
            ->with('success', 'Kundli deleted successfully.');
    }
}
