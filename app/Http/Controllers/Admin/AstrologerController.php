<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
            'years_of_experience' => 'nullable|integer|min:0',
            'areas_of_expertise' => 'nullable|string',
            'languages' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_proof_number' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'chat_enabled' => 'sometimes|boolean',
            'call_enabled' => 'sometimes|boolean',
            'video_call_enabled' => 'sometimes|boolean',
            'chat_rate_per_minute' => 'nullable|numeric|min:0',
            'call_rate_per_minute' => 'nullable|numeric|min:0',
            'video_call_rate_per_minute' => 'nullable|numeric|min:0',
            'po_at_5_enabled' => 'sometimes|boolean',
            'po_at_5_rate_per_minute' => 'nullable|numeric|min:0',
            'po_at_5_sessions' => 'nullable|integer|min:0',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'country' => $request->country,
            'password' => Hash::make($request->password),
            'user_type' => 'astrologer',
        ]);

        $astrologerData = [
            'user_id' => $user->id,
            'years_of_experience' => $request->input('years_of_experience'),
            'areas_of_expertise' => $request->input('areas_of_expertise') ? array_map('trim', explode(',', $request->input('areas_of_expertise'))) : null,
            'languages' => $request->input('languages') ? array_map('trim', explode(',', $request->input('languages'))) : null,
            'bio' => $request->input('bio'),
            'id_proof_number' => $request->input('id_proof_number'),
            'date_of_birth' => $request->input('date_of_birth'),
            'status' => $request->input('status'),
            'chat_enabled' => $request->has('chat_enabled'),
            'call_enabled' => $request->has('call_enabled'),
            'video_call_enabled' => $request->has('video_call_enabled'),
            'chat_rate_per_minute' => $request->input('chat_rate_per_minute'),
            'call_rate_per_minute' => $request->input('call_rate_per_minute'),
            'video_call_rate_per_minute' => $request->input('video_call_rate_per_minute'),
            'po_at_5_enabled' => $request->has('po_at_5_enabled'),
            'po_at_5_rate_per_minute' => $request->input('po_at_5_rate_per_minute'),
            'po_at_5_sessions' => $request->input('po_at_5_sessions'),
        ];

        // Handle file uploads
        $astrologerData += $this->handleFileUploads($request, $user->id);

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
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'years_of_experience' => 'nullable|integer|min:0',
            'areas_of_expertise' => 'nullable|string',
            'languages' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_proof_number' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'chat_enabled' => 'sometimes|boolean',
            'call_enabled' => 'sometimes|boolean',
            'video_call_enabled' => 'sometimes|boolean',
            'chat_rate_per_minute' => 'nullable|numeric|min:0',
            'call_rate_per_minute' => 'nullable|numeric|min:0',
            'video_call_rate_per_minute' => 'nullable|numeric|min:0',
            'po_at_5_enabled' => 'sometimes|boolean',
            'po_at_5_rate_per_minute' => 'nullable|numeric|min:0',
            'po_at_5_sessions' => 'nullable|integer|min:0',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'country' => $request->country,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        $astrologer = Astrologer::firstOrCreate(['user_id' => $user->id]);

        $astrologerUpdateData = [
            'years_of_experience' => $request->input('years_of_experience'),
            'areas_of_expertise' => $request->input('areas_of_expertise') ? array_map('trim', explode(',', $request->input('areas_of_expertise'))) : null,
            'languages' => $request->input('languages') ? array_map('trim', explode(',', $request->input('languages'))) : null,
            'bio' => $request->input('bio'),
            'id_proof_number' => $request->input('id_proof_number'),
            'date_of_birth' => $request->input('date_of_birth'),
            'status' => $request->input('status'),
            'chat_enabled' => $request->has('chat_enabled'),
            'call_enabled' => $request->has('call_enabled'),
            'video_call_enabled' => $request->has('video_call_enabled'),
            'chat_rate_per_minute' => $request->input('chat_rate_per_minute'),
            'call_rate_per_minute' => $request->input('call_rate_per_minute'),
            'video_call_rate_per_minute' => $request->input('video_call_rate_per_minute'),
            'po_at_5_enabled' => $request->has('po_at_5_enabled'),
            'po_at_5_rate_per_minute' => $request->input('po_at_5_rate_per_minute'),
            'po_at_5_sessions' => $request->input('po_at_5_sessions'),
        ];

        // Handle file uploads  
        $astrologerUpdateData += $this->handleFileUploads($request, $user->id, $astrologer);

        $astrologer->update($astrologerUpdateData);

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer updated successfully.');
    }

    /**
     * Handle file uploads for astrologer documents.
     * 
     * @param Request $request
     * @param int $userId
     * @param Astrologer|null $astrologer
     * @return array
     */
    private function handleFileUploads(Request $request, int $userId, ?Astrologer $astrologer = null): array
    {
        $data = [];
        $fileFields = ['profile_photo', 'id_proof', 'certificate'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                
                // Generate unique filename
                $filename = time() . '_' . $userId . '_' . $field . '.' . $file->getClientOriginalExtension();
                
                // Determine storage path based on field type
                if ($field === 'profile_photo') {
                    $path = 'astrologers/' . $userId . '/profile_photo';
                } else {
                    $path = 'astrologers/' . $userId . '/documents';
                }

                // Delete old file if exists (for updates)
                if ($astrologer && isset($astrologer->{$field}) && $astrologer->{$field}) {
                    $oldPath = str_replace('/storage/', '', $astrologer->{$field});
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                // Store the new file
                $storagePath = $file->storeAs($path, $filename, 'public');
                $data[$field] = '/storage/' . $storagePath;
            }
        }

        return $data;
    }

    public function destroy($id)
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);
        $user->delete();

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer deleted successfully.');
    }
}
