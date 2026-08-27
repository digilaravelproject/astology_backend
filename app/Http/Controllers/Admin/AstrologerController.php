<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerBankAccount;
use App\Models\AstrologerBillingAddress;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerDefaultMessage;
use App\Models\AstrologerGallery;
use App\Models\AstrologerOtherDetail;
use App\Models\AstrologerPackage;
use App\Models\AstrologerPayout;
use App\Models\AstrologerPhoneNumber;
use App\Models\AstrologerReview;
use App\Models\AstrologerSkill;
use App\Models\Setting;
use App\Models\User;
use App\Services\AstrologerService;
use App\Services\NotificationHelper;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AstrologerController extends Controller
{
    // =========================================================================
    // 1. LISTING & SEARCH
    // =========================================================================

    /**
     * Display a paginated listing of astrologers with filters.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('user_type', 'astrologer')
            ->with(['astrologer', 'astrologer.galleries']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
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

    // =========================================================================
    // 2. CREATE & STORE
    // =========================================================================

    /**
     * Show the form for creating a new astrologer.
     */
    public function create(): View
    {
        return view('admin.astrologers.form', ['user' => null]);
    }

    /**
     * Store a newly created astrologer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validateAstrologer($request);

        $user = User::create([
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'phone'     => $request->input('phone'),
            'city'      => $request->input('city'),
            'country'   => $request->input('country'),
            'password'  => Hash::make($request->input('password')),
            'user_type' => 'astrologer',
        ]);

        $astrologerData = $this->buildAstrologerPayload($request, $user->id);
        $astrologerData += $this->handleFileUploads($request, $user->id);

        Astrologer::create($astrologerData);

        // Invalidate catalog cache for immediate discovery
        AstrologerService::flushCatalogCache();

        return redirect()->route('admin.astrologers.index')
            ->with('success', 'Astrologer created successfully.');
    }

    // =========================================================================
    // 3. SHOW & EDIT
    // =========================================================================

    /**
     * Display the specified astrologer profile and detailed stats.
     */
    public function show(int|string $id): View
    {
        $user = User::where('user_type', 'astrologer')
            ->with(['astrologer', 'wallet'])
            ->findOrFail($id);

        $billingAddress = $user->astrologer
            ? AstrologerBillingAddress::where('astrologer_id', $user->astrologer->id)->first()
            : null;

        return view('admin.astrologers.show', compact('user', 'billingAddress'));
    }

    /**
     * Show the form for editing the specified astrologer.
     */
    public function edit(int|string $id): View
    {
        $user = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->findOrFail($id);

        return view('admin.astrologers.form', compact('user'));
    }

    // =========================================================================
    // 4. UPDATE & SYNC
    // =========================================================================

    /**
     * Update the specified astrologer in storage.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);

        $this->validateAstrologer($request, $user->id);

        $userData = [
            'name'    => $request->input('name'),
            'email'   => $request->input('email'),
            'phone'   => $request->input('phone'),
            'city'    => $request->input('city'),
            'country' => $request->input('country'),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->input('password'));
        }

        $user->update($userData);

        $astrologer = Astrologer::firstOrCreate(['user_id' => $user->id]);
        $oldStatus = $astrologer->status;
        $newStatus = $request->input('status');

        $astrologerUpdateData = $this->buildAstrologerPayload($request, $user->id);
        $astrologerUpdateData += $this->handleFileUploads($request, $user->id, $astrologer);

        $astrologer->update($astrologerUpdateData);

        // Send approval / rejection notification if status transitioned
        if ($oldStatus !== $newStatus) {
            $this->sendStatusNotification($user->id, $newStatus);
        }

        // Sync custom packages if provided
        $this->syncCustomPackages($request, $user->id);

        // Invalidate Redis catalog cache
        AstrologerService::flushCatalogCache();

        return redirect()->route('admin.astrologers.index')
            ->with('success', 'Astrologer updated successfully.');
    }

    // =========================================================================
    // 5. QUICK STATUS MANAGEMENT
    // =========================================================================

    /**
     * Quick status update for astrologer from admin panel.
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse|RedirectResponse
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
            if ($astrologer->chat_rate_per_minute === null) {
                $astrologer->chat_rate_per_minute = Setting::get('default_chat_rate_per_minute', 15.00);
            }
            if ($astrologer->call_rate_per_minute === null) {
                $astrologer->call_rate_per_minute = Setting::get('default_call_rate_per_minute', 15.00);
            }
        }
        $astrologer->save();

        // Flush catalog cache so frontend & mobile instantly get updated listing
        AstrologerService::flushCatalogCache();

        // Broadcast availability update
        try {
            $isOnline = (bool) ($astrologer->is_online || $astrologer->is_chat_enabled || $astrologer->is_call_enabled || $astrologer->is_video_call_enabled);
            app(PresenceService::class)->broadcastAstrologerAvailability(
                $user->id,
                $newStatus === 'approved' ? $isOnline : false,
                (bool) ($user->is_busy ?? false)
            );
        } catch (\Throwable $e) {
            Log::warning('Broadcast on admin astrologer status update failed: ' . $e->getMessage());
        }

        // Notify astrologer on approval or rejection
        if ($oldStatus !== $newStatus) {
            $this->sendStatusNotification($user->id, $newStatus);
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

    // =========================================================================
    // 6. PERMANENT CASCADE DELETION
    // =========================================================================

    /**
     * Completely and safely delete an astrologer, all associated records, media files, and cache.
     */
    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = User::where('user_type', 'astrologer')->findOrFail($id);
        $astrologer = $user->astrologer;

        DB::transaction(function () use ($user, $astrologer) {
            // 1. Delete all uploaded documents and images from disk storage
            try {
                Storage::disk('public')->deleteDirectory('astrologers/' . $user->id);
            } catch (\Throwable $e) {
                Log::warning("Failed to delete astrologer storage directory: " . $e->getMessage());
            }

            // 2. Cascade delete all astrologer child relations
            if ($astrologer) {
                AstrologerBankAccount::where('astrologer_id', $astrologer->id)->delete();
                AstrologerGallery::where('astrologer_id', $astrologer->id)->delete();
                AstrologerPhoneNumber::where('astrologer_id', $astrologer->id)->delete();
                AstrologerSkill::where('astrologer_id', $astrologer->id)->delete();
                AstrologerOtherDetail::where('astrologer_id', $astrologer->id)->delete();
                AstrologerCommunity::where('astrologer_id', $astrologer->id)->delete();
                AstrologerReview::where('astrologer_id', $astrologer->id)->delete();
                AstrologerBillingAddress::where('astrologer_id', $astrologer->id)->delete();
                AstrologerDefaultMessage::where('astrologer_id', $astrologer->id)->delete();
                AstrologerPayout::where('user_id', $user->id)->orWhere('astrologer_id', $astrologer->id)->delete();
                AstrologerPackage::where('astrologer_id', $user->id)->delete();

                // Detach offers and delete profile
                $astrologer->offers()->detach();
                $astrologer->delete();
            }

            // 3. Delete user account and tokens
            $user->tokens()->delete();
            $user->delete();

            // 4. Invalidate Redis catalog cache
            AstrologerService::flushCatalogCache();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Astrologer and all associated data completely deleted successfully.',
            ]);
        }

        return redirect()->route('admin.astrologers.index')
            ->with('success', 'Astrologer and all associated data completely deleted successfully.');
    }

    // =========================================================================
    // 7. INTERNAL HELPERS & FACTORIES
    // =========================================================================

    /**
     * Validate incoming astrologer request.
     */
    private function validateAstrologer(Request $request, ?int $ignoreUserId = null): void
    {
        $rules = [
            'name'                          => 'required|string|max:255',
            'email'                         => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($ignoreUserId)],
            'phone'                         => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($ignoreUserId)],
            'city'                          => 'nullable|string|max:255',
            'country'                       => 'nullable|string|max:255',
            'password'                      => $ignoreUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'years_of_experience'           => 'nullable|integer|min:0',
            'areas_of_expertise'            => 'nullable|string',
            'languages'                     => 'nullable|string',
            'bio'                           => 'nullable|string|max:1000',
            'profile_photo'                 => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'id_proof'                      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate'                   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_proof_number'               => 'nullable|string|max:100',
            'date_of_birth'                 => 'nullable|date',
            'chat_enabled'                  => 'sometimes|boolean',
            'call_enabled'                  => 'sometimes|boolean',
            'video_call_enabled'            => 'sometimes|boolean',
            'chat_rate_per_minute'          => 'nullable|numeric|min:0',
            'call_rate_per_minute'          => 'nullable|numeric|min:0',
            'video_call_rate_per_minute'    => 'nullable|numeric|min:0',
            'po_at_5_enabled'               => 'sometimes|boolean',
            'po_at_5_rate_per_minute'       => 'nullable|numeric|min:0',
            'po_at_5_sessions'              => 'nullable|integer|min:0',
            'status'                        => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'package_amount'                => 'nullable|numeric|min:0',
            'package_duration_minutes'      => 'nullable|numeric|min:0.1',
            'package_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ];

        $request->validate($rules);
    }

    /**
     * Build standard astrologer profile payload from request.
     */
    private function buildAstrologerPayload(Request $request, int $userId): array
    {
        return [
            'user_id'                    => $userId,
            'years_of_experience'        => $request->input('years_of_experience'),
            'areas_of_expertise'         => $request->input('areas_of_expertise') ? array_map('trim', explode(',', $request->input('areas_of_expertise'))) : null,
            'languages'                  => $request->input('languages') ? array_map('trim', explode(',', $request->input('languages'))) : null,
            'bio'                        => $request->input('bio'),
            'id_proof_number'            => $request->input('id_proof_number'),
            'date_of_birth'              => $request->input('date_of_birth'),
            'status'                     => $request->input('status'),
            'chat_enabled'               => $request->has('chat_enabled'),
            'call_enabled'               => $request->has('call_enabled'),
            'video_call_enabled'         => $request->has('video_call_enabled'),
            'chat_rate_per_minute'       => $request->input('chat_rate_per_minute') ?? Setting::get('default_chat_rate_per_minute', 15.00),
            'call_rate_per_minute'       => $request->input('call_rate_per_minute') ?? Setting::get('default_call_rate_per_minute', 15.00),
            'video_call_rate_per_minute' => $request->input('video_call_rate_per_minute') ?? Setting::get('default_video_call_rate_per_minute', 15.00),
            'po_at_5_enabled'            => $request->has('po_at_5_enabled'),
            'po_at_5_rate_per_minute'    => $request->input('po_at_5_rate_per_minute') ?? Setting::get('default_po_at_5_rate_per_minute', 5.00),
            'po_at_5_sessions'           => $request->input('po_at_5_sessions'),
        ];
    }

    /**
     * Handle file uploads for astrologer profile photo, ID proof, and certificates.
     */
    private function handleFileUploads(Request $request, int $userId, ?Astrologer $astrologer = null): array
    {
        $data = [];
        $fileFields = ['profile_photo', 'id_proof', 'certificate'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                $filename = time() . '_' . $userId . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = ($field === 'profile_photo')
                    ? 'astrologers/' . $userId . '/profile_photo'
                    : 'astrologers/' . $userId . '/documents';

                // Delete previous file if exists
                if ($astrologer && !empty($astrologer->{$field})) {
                    $oldPath = str_replace('/storage/', '', $astrologer->{$field});
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $storagePath = $file->storeAs($path, $filename, 'public');
                $data[$field] = $storagePath;
            }
        }

        return $data;
    }

    /**
     * Dispatch notification for status changes.
     */
    private function sendStatusNotification(int $userId, string $newStatus): void
    {
        try {
            if ($newStatus === 'approved') {
                NotificationHelper::send(
                    userId: $userId,
                    title: 'Profile Approved! 🎉',
                    body: 'Congratulations! Your astrologer profile has been approved. You can now go online and accept consultations.',
                    meta: [
                        'type'         => 'system',
                        'screen_route' => '/profile',
                    ]
                );
            } elseif ($newStatus === 'rejected') {
                NotificationHelper::send(
                    userId: $userId,
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

    /**
     * Sync astrologer custom package overrides.
     */
    private function syncCustomPackages(Request $request, int $userId): void
    {
        if ($request->filled('package_amount') || $request->filled('package_duration_minutes')) {
            AstrologerPackage::updateOrCreate(
                ['astrologer_id' => $userId],
                [
                    'amount'                => $request->input('package_amount'),
                    'duration'              => (int) ($request->input('package_duration_minutes') * 60),
                    'commission_percentage' => $request->input('package_commission_percentage'),
                ]
            );
        } else {
            AstrologerPackage::where('astrologer_id', $userId)->delete();
        }
    }
}
