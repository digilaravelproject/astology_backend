<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\User;
use App\Models\AstrologerBillingAddress;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AstrologerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('user_type', 'astrologer')
            ->with('astrologer', 'astrologer.galleries');

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
            'chat_rate_per_minute' => $request->input('chat_rate_per_minute') ?? Setting::get('default_chat_rate_per_minute', 15.00),
            'call_rate_per_minute' => $request->input('call_rate_per_minute') ?? Setting::get('default_call_rate_per_minute', 15.00),
            'video_call_rate_per_minute' => $request->input('video_call_rate_per_minute') ?? Setting::get('default_video_call_rate_per_minute', 15.00),
            'po_at_5_enabled' => $request->has('po_at_5_enabled'),
            'po_at_5_rate_per_minute' => $request->input('po_at_5_rate_per_minute') ?? Setting::get('default_po_at_5_rate_per_minute', 5.00),
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

        $billingAddress = AstrologerBillingAddress::where('astrologer_id', $user->astrologer->id)->first();

        return view('admin.astrologers.show', compact('user', 'billingAddress'));
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
            
            // Package overrides validation
            'package_amount' => 'nullable|numeric|min:0',
            'package_duration_minutes' => 'nullable|numeric|min:0.1',
            'package_commission_percentage' => 'nullable|numeric|min:0|max:100',
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
            'chat_rate_per_minute' => $request->input('chat_rate_per_minute') ?? Setting::get('default_chat_rate_per_minute', 15.00),
            'call_rate_per_minute' => $request->input('call_rate_per_minute') ?? Setting::get('default_call_rate_per_minute', 15.00),
            'video_call_rate_per_minute' => $request->input('video_call_rate_per_minute') ?? Setting::get('default_video_call_rate_per_minute', 15.00),
            'po_at_5_enabled' => $request->has('po_at_5_enabled'),
            'po_at_5_rate_per_minute' => $request->input('po_at_5_rate_per_minute') ?? Setting::get('default_po_at_5_rate_per_minute', 5.00),
            'po_at_5_sessions' => $request->input('po_at_5_sessions'),
        ];

        // Handle file uploads  
        $astrologerUpdateData += $this->handleFileUploads($request, $user->id, $astrologer);

        $oldStatus = $astrologer->status;
        $newStatus = $request->input('status');

        $astrologer->update($astrologerUpdateData);

        // Notify astrologer on approval or rejection
        if ($oldStatus !== $newStatus) {
            try {
                if ($newStatus === 'approved') {
                    \App\Services\NotificationHelper::send(
                        userId: $user->id,
                        title: 'Profile Approved! 🎉',
                        body: 'Congratulations! Your astrologer profile has been approved. You can now go online and accept consultations.',
                        meta: [
                            'type'         => 'system',
                            'screen_route' => '/profile',
                        ]
                    );
                } elseif ($newStatus === 'rejected') {
                    \App\Services\NotificationHelper::send(
                        userId: $user->id,
                        title: 'Profile Update Required ⚠️',
                        body: 'Your astrologer profile could not be approved at this time. Please update your details or contact support.',
                        meta: [
                            'type'         => 'system',
                            'screen_route' => '/profile',
                        ]
                    );
                }
            } catch (\Throwable $ne) {
                Log::error('Astrologer status change notification failed: ' . $ne->getMessage());
            }
        }

        // Sync Astrologer Custom Package overrides if any are provided
        if ($request->filled('package_amount') || $request->filled('package_duration_minutes')) {
            \App\Models\AstrologerPackage::updateOrCreate(
                ['astrologer_id' => $user->id],
                [
                    'amount' => $request->input('package_amount'),
                    'duration' => (int) ($request->input('package_duration_minutes') * 60), // store as seconds
                    'commission_percentage' => $request->input('package_commission_percentage')
                ]
            );
        } else {
            // Remove overrides so they fallback to global defaults if cleared
            \App\Models\AstrologerPackage::where('astrologer_id', $user->id)->delete();
        }

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
                $data[$field] = $storagePath;
            }
        }

        return $data;
    }

    /**
     * Quick status update for astrologer from admin panel (e.g. approved, pending, rejected, suspended).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $user = User::where('user_type', 'astrologer')->findOrFail($id);
        $astrologer = Astrologer::firstOrCreate(['user_id' => $user->id]);

        $oldStatus = $astrologer->status;
        $newStatus = $request->input('status');

        $astrologer->status = $newStatus;
        if ($newStatus === 'approved') {
            // Ensure default pricing flags if not configured
            if ($astrologer->chat_rate_per_minute === null) {
                $astrologer->chat_rate_per_minute = Setting::get('default_chat_rate_per_minute', 15.00);
            }
            if ($astrologer->call_rate_per_minute === null) {
                $astrologer->call_rate_per_minute = Setting::get('default_call_rate_per_minute', 15.00);
            }
        }
        $astrologer->save();

        // Flush catalog cache so mobile/frontend instantly gets updated listing
        \App\Services\AstrologerService::flushCatalogCache();

        // Broadcast availability update if needed
        try {
            $isOnline = (bool) ($astrologer->is_online || $astrologer->is_chat_enabled || $astrologer->is_call_enabled || $astrologer->is_video_call_enabled);
            app(\App\Services\PresenceService::class)->broadcastAstrologerAvailability(
                $user->id,
                $newStatus === 'approved' ? $isOnline : false,
                (bool) ($user->is_busy ?? false)
            );
        } catch (\Throwable $e) {
            Log::warning('Broadcast on admin astrologer status update failed: ' . $e->getMessage());
        }

        // Notify astrologer on approval or rejection
        if ($oldStatus !== $newStatus) {
            try {
                if ($newStatus === 'approved') {
                    \App\Services\NotificationHelper::send(
                        userId: $user->id,
                        title: 'Profile Approved! 🎉',
                        body: 'Congratulations! Your astrologer profile has been approved. You can now go online and accept consultations.',
                        meta: [
                            'type'         => 'system',
                            'screen_route' => '/profile',
                        ]
                    );
                } elseif ($newStatus === 'rejected') {
                    \App\Services\NotificationHelper::send(
                        userId: $user->id,
                        title: 'Profile Update Required ⚠️',
                        body: 'Your astrologer profile could not be approved at this time. Please update your details or contact support.',
                        meta: [
                            'type'         => 'system',
                            'screen_route' => '/profile',
                        ]
                    );
                }
            } catch (\Throwable $ne) {
                Log::error('Astrologer status change notification failed: ' . $ne->getMessage());
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Astrologer status updated to {$newStatus} successfully.",
                'data'    => [
                    'id'     => $user->id,
                    'status' => $newStatus,
                ],
            ]);
        }

        return redirect()->back()->with('success', "Astrologer status updated to {$newStatus} successfully.");
    }

    /**
     * Completely and safely delete an astrologer, all associated records, media files and cache.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);
        $astrologer = $user->astrologer;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $astrologer) {
            // 1. Delete all uploaded documents and images from storage
            try {
                Storage::disk('public')->deleteDirectory('astrologers/' . $user->id);
            } catch (\Throwable $e) {
                Log::warning("Failed to delete astrologer storage directory: " . $e->getMessage());
            }

            // 2. Cascade delete all astrologer child relations
            if ($astrologer) {
                \App\Models\AstrologerBankAccount::where('astrologer_id', $astrologer->id)->orWhere('user_id', $user->id)->delete();
                \App\Models\AstrologerGallery::where('astrologer_id', $astrologer->id)->delete();
                \App\Models\AstrologerPhoneNumber::where('astrologer_id', $astrologer->id)->delete();
                \App\Models\AstrologerSkill::where('astrologer_id', $astrologer->id)->delete();
                \App\Models\AstrologerOtherDetail::where('astrologer_id', $astrologer->id)->delete();
                \App\Models\AstrologerCommunity::where('astrologer_id', $astrologer->id)->orWhere('user_id', $user->id)->delete();
                \App\Models\AstrologerReview::where('astrologer_id', $astrologer->id)->delete();
                \App\Models\AstrologerPackage::where('astrologer_id', $user->id)->delete();
                
                // Detach any offers
                $astrologer->offers()->detach();
                $astrologer->delete();
            }

            // 3. Delete user account and tokens
            $user->tokens()->delete();
            $user->delete();

            // 4. Invalidate Redis catalog cache
            \App\Services\AstrologerService::flushCatalogCache();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Astrologer and all associated data completely deleted successfully.',
            ]);
        }

        return redirect()->route('admin.astrologers.index')->with('success', 'Astrologer and all associated data completely deleted successfully.');
    }
}
