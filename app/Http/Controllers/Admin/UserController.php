<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\Plan;
use App\Models\User;
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
        // Default to regular users unless a specific type is requested
        $type = $request->input('type', 'user');

        $query = User::query()->latest()->where('user_type', $type);

        // Filtering logic
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, ['active', 'inactive'])) {
                $query->where('profile_completed', $status === 'active');
            }
        }

        // Include relationships we show on the list
        $query->with(['astrologer', 'wallet']);

        $users = $query->paginate(15)->appends($request->all());

        $totalUsers = User::where('user_type', 'user')->count();

        return view('admin.users.index', compact('users', 'totalUsers', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $plans = Plan::pluck('name', 'id');
        return view('admin.users.form', compact('plans'))->with('user', null);
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
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|url|max:255',
            'gender' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'time_of_birth' => 'nullable',
            'place_of_birth' => 'nullable|string|max:255',
            'languages' => 'nullable|string|max:255',
            'otp' => 'nullable|string|max:20',
            'otp_expires_at' => 'nullable|date',
            'otp_verified_at' => 'nullable|date',
            'profile_completed' => 'sometimes|boolean',
            'plan_id' => 'nullable|exists:plans,id',
            'plan_started_at' => 'nullable|date',
            'plan_expires_at' => 'nullable|date',
            'is_online' => 'sometimes|boolean',
            'is_busy' => 'sometimes|boolean',
            'busy_session_id' => 'nullable|integer',
            'last_seen_at' => 'nullable|date',
            'fcm_token' => 'nullable|string|max:255',
            'email_verified_at' => 'nullable|date',
        ]);

        $data = $this->prepareUserData($request);
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

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
        $plans = Plan::pluck('name', 'id');
        return view('admin.users.form', compact('user', 'plans'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::with(['astrologer', 'wallet'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
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
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|url|max:255',
            'gender' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'time_of_birth' => 'nullable',
            'place_of_birth' => 'nullable|string|max:255',
            'languages' => 'nullable|string|max:255',
            'otp' => 'nullable|string|max:20',
            'otp_expires_at' => 'nullable|date',
            'otp_verified_at' => 'nullable|date',
            'profile_completed' => 'sometimes|boolean',
            'plan_id' => 'nullable|exists:plans,id',
            'plan_started_at' => 'nullable|date',
            'plan_expires_at' => 'nullable|date',
            'is_online' => 'sometimes|boolean',
            'is_busy' => 'sometimes|boolean',
            'busy_session_id' => 'nullable|integer',
            'last_seen_at' => 'nullable|date',
            'fcm_token' => 'nullable|string|max:255',
            'email_verified_at' => 'nullable|date',
        ]);

        $data = $this->prepareUserData($request);

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
        
        if ($user->astrologer) {
            $user->astrologer->delete();
        }
        
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    protected function prepareUserData(Request $request): array
    {
        return [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'user_type' => $request->user_type,
            'city' => $request->city,
            'country' => $request->country,
            'profile_photo' => $request->profile_photo,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'time_of_birth' => $request->time_of_birth,
            'place_of_birth' => $request->place_of_birth,
            'languages' => $request->filled('languages') ? array_values(array_filter(array_map('trim', explode(',', $request->languages)))) : [],
            'otp' => $request->otp,
            'otp_expires_at' => $request->otp_expires_at,
            'otp_verified_at' => $request->otp_verified_at,
            'email_verified_at' => $request->email_verified_at,
            'profile_completed' => $request->has('profile_completed'),
            'plan_id' => $request->plan_id,
            'plan_started_at' => $request->plan_started_at,
            'plan_expires_at' => $request->plan_expires_at,
            'is_online' => $request->has('is_online'),
            'is_busy' => $request->has('is_busy'),
            'busy_session_id' => $request->busy_session_id,
            'last_seen_at' => $request->last_seen_at,
            'fcm_token' => $request->fcm_token,
        ];
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
