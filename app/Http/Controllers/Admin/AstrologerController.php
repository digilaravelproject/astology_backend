<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AstrologerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('user_type', 'astrologer')
            ->with('astrologer');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereHas('astrologer', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $astrologers = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $totalAstrologers = User::where('user_type', 'astrologer')->count();

        return view('admin.astrologers.index', compact('astrologers', 'totalAstrologers'));
    }

    public function create()
    {
        // For the create form we do not have an existing record, so pass null to avoid route generation errors.
        return view('admin.astrologers.form', ['user' => null]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8',
            'years_of_experience' => 'nullable|integer|min:0',
            'areas_of_expertise' => 'nullable|string',
            'languages' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => 'astrologer',
        ]);

        $astrologerData = [
            'user_id' => $user->id,
            'years_of_experience' => $request->input('years_of_experience'),
            'areas_of_expertise' => $request->input('areas_of_expertise') ? array_map('trim', explode(',', $request->input('areas_of_expertise'))) : null,
            'languages' => $request->input('languages') ? array_map('trim', explode(',', $request->input('languages'))) : null,
            'profile_photo' => $request->input('profile_photo'),
            'bio' => $request->input('bio'),
            'id_proof' => $request->input('id_proof'),
            'certificate' => $request->input('certificate'),
            'id_proof_number' => $request->input('id_proof_number'),
            'date_of_birth' => $request->input('date_of_birth'),
            'status' => $request->input('status'),
        ];

        Astrologer::create($astrologerData);

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer created successfully.');
    }

    public function show($id)
    {
        $user = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->findOrFail($id);

        return view('admin.astrologers.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->findOrFail($id);

        return view('admin.astrologers.form', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'years_of_experience' => 'nullable|integer|min:0',
            'areas_of_expertise' => 'nullable|string',
            'languages' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $astrologer = Astrologer::firstOrCreate(['user_id' => $user->id]);

        $astrologer->update([
            'years_of_experience' => $request->input('years_of_experience'),
            'areas_of_expertise' => $request->input('areas_of_expertise') ? array_map('trim', explode(',', $request->input('areas_of_expertise'))) : null,
            'languages' => $request->input('languages') ? array_map('trim', explode(',', $request->input('languages'))) : null,
            'profile_photo' => $request->input('profile_photo'),
            'bio' => $request->input('bio'),
            'id_proof' => $request->input('id_proof'),
            'certificate' => $request->input('certificate'),
            'id_proof_number' => $request->input('id_proof_number'),
            'date_of_birth' => $request->input('date_of_birth'),
            'status' => $request->input('status'),
        ]);

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);
        $user->delete();

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer deleted successfully.');
    }
}
