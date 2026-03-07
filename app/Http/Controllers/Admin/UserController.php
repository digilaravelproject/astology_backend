<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Astrologer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->latest();

        // Filtering logic
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('user_type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereHas('astrologer', function($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // Include astrologer relationship
        $query->with('astrologer');

        $users = $query->paginate(15)->appends($request->all());

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8',
            'user_type' => 'required|in:user,astrologer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
        ]);

        if ($request->user_type === 'astrologer') {
            Astrologer::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'years_of_experience' => $request->years_of_experience ?? 0,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::with('astrologer')->findOrFail($id);
        return view('admin.users.form', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'user_type' => 'required|in:user,astrologer',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'user_type' => $request->user_type,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Manage astrologer profile
        if ($request->user_type === 'astrologer') {
            $astrologer = Astrologer::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'pending']
            );
            
            if ($request->has('years_of_experience')) {
                $astrologer->update([
                    'years_of_experience' => $request->years_of_experience,
                ]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Due to foreign key constraints, we delete the astrologer profile if exists
        // Or if database has cascade, it will automatically delete
        if ($user->astrologer) {
            $user->astrologer->delete();
        }
        
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle the status of an astrologer.
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::with('astrologer')->findOrFail($id);

        if ($user->user_type !== 'astrologer' || !$user->astrologer) {
            return back()->with('error', 'User is not an astrologer.');
        }

        $newStatus = $request->input('status');
        
        if (in_array($newStatus, ['approved', 'pending', 'rejected'])) {
            $user->astrologer->update(['status' => $newStatus]);
            return back()->with('success', 'Astrologer status updated successfully.');
        }

        return back()->with('error', 'Invalid status.');
    }
}
