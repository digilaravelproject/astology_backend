<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatrimonyProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MatrimonyController extends Controller
{
    public function index(Request $request)
    {
        $query = MatrimonyProfile::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('education', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
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

        $profiles = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $total = MatrimonyProfile::count();
        $active = MatrimonyProfile::where('is_active', true)->count();
        $inactive = MatrimonyProfile::where('is_active', false)->count();

        return view('admin.matrimonies.index', compact('profiles', 'total', 'active', 'inactive'));
    }

    public function create()
    {
        $users = User::orderBy('name')->limit(200)->get();
        return view('admin.matrimonies.form', ['profile' => new MatrimonyProfile(), 'users' => $users]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'created_for' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'height' => 'nullable|string|max:50',
            'marital_status' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'annual_income' => 'nullable|string|max:255',
            'about' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|file|image|max:5120',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = time() . '_' . $request->user_id . '_matrimony.' . $file->getClientOriginalExtension();
            $path = 'matrimony_profiles/' . $request->user_id;
            $data['profile_photo'] = Storage::disk('public')->putFileAs($path, $file, $filename);
        }

        MatrimonyProfile::create($data);

        return redirect()->route('admin.matrimonies.index')->with('success', 'Matrimony profile created successfully.');
    }

    public function edit($id)
    {
        $profile = MatrimonyProfile::findOrFail($id);
        $users = User::orderBy('name')->limit(200)->get();
        return view('admin.matrimonies.form', compact('profile', 'users'));
    }

    public function update(Request $request, $id)
    {
        $profile = MatrimonyProfile::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'created_for' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'height' => 'nullable|string|max:50',
            'marital_status' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'annual_income' => 'nullable|string|max:255',
            'about' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|file|image|max:5120',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = time() . '_' . $request->user_id . '_matrimony.' . $file->getClientOriginalExtension();
            $path = 'matrimony_profiles/' . $request->user_id;
            if ($profile->profile_photo && Storage::disk('public')->exists($profile->profile_photo)) {
                Storage::disk('public')->delete($profile->profile_photo);
            }
            $data['profile_photo'] = Storage::disk('public')->putFileAs($path, $file, $filename);
        }

        $profile->update($data);

        return redirect()->route('admin.matrimonies.index')->with('success', 'Matrimony profile updated successfully.');
    }

    public function destroy($id)
    {
        $profile = MatrimonyProfile::findOrFail($id);
        $profile->delete();

        return redirect()->route('admin.matrimonies.index')->with('success', 'Matrimony profile deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $profile = MatrimonyProfile::findOrFail($id);
        $profile->is_active = !$profile->is_active;
        $profile->save();

        return redirect()->route('admin.matrimonies.index')->with('success', 'Profile status updated successfully.');
    }
}
