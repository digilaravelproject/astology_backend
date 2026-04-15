<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AstrologerSignupRequest;
use App\Http\Requests\UpdateAstrologerProfilePhotoRequest;
use App\Http\Requests\UpdateAstrologerProfileRequest;
use App\Http\Requests\UpdateAstrologerSkillRequest;
use App\Http\Requests\UpdateAstrologerOtherDetailsRequest;
use App\Http\Requests\UpdateAstrologerHomeRequest;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\AstrologerPhoneNumber;
use App\Models\AstrologerBankAccount;
use App\Services\NotificationHelper;
use App\Models\AstrologerSkill;
use App\Models\AstrologerOtherDetail;
use App\Models\AstrologerCommunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AstrologerAuthController extends Controller
{
    /**
     * Register a new astrologer.
     *
     * @param AstrologerSignupRequest $request
     * @return JsonResponse
     */

    public function signup(AstrologerSignupRequest $request): JsonResponse
    {
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Validate and get the request data
            $validated = $request->validated();

            // Step 1: Create user with basic details
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'user_type' => 'astrologer',
                'password' => bcrypt($validated['phone']), // Default password using phone
            ]);

            // Initialize file path array for storing uploaded files
            $uploadedFiles = [
                'profile_photo' => null,
                'id_proof' => null,
                'certificate' => null,
            ];

            // Step 2: Handle file uploads with loop validation
            $fileFields = ['profile_photo', 'id_proof', 'certificate'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    try {
                        $file = $request->file($field);
                        $filename = time() . '_' . $user->id . '_' . $field . '.' . $file->getClientOriginalExtension();
                        $path = 'astrologers/' . $user->id . '/' . $field;

                        // Store file in storage
                        $uploadedFiles[$field] = Storage::disk('public')->putFileAs($path, $file, $filename);

                    } catch (\Exception $e) {
                        // Log file upload error but continue
                        Log::error("File upload error for {$field}: " . $e->getMessage());
                        // Don't throw error, just skip this file
                    }
                }
            }

            // Step 3: Validate and process areas of expertise array
            $areasOfExpertise = [];
            $validAreas = [
                'Vedic Astrology',
                'Tarot',
                'Numerology',
                'Palmistry',
                'Vastu',
                'KP Astrology',
                'Nadi Astrology',
                'Feng Shui',
                'Face Reading',
                'Prashna'
            ];

            if (isset($validated['areas_of_expertise']) && is_array($validated['areas_of_expertise'])) {
                foreach ($validated['areas_of_expertise'] as $area) {
                    if (in_array($area, $validAreas)) {
                        $areasOfExpertise[] = $area;
                    }
                }
            }

            // Step 4: Validate and process languages array
            $languages = [];
            $validLanguages = [
                'Hindi',
                'English',
                'Bengali',
                'Tamil',
                'Telugu',
                'Marathi',
                'Gujarati',
                'Kannada',
                'Malayalam',
                'Punjabi',
                'Odia',
                'Urdu'
            ];

            if (isset($validated['languages']) && is_array($validated['languages'])) {
                foreach ($validated['languages'] as $language) {
                    if (in_array($language, $validLanguages)) {
                        $languages[] = $language;
                    }
                }
            }

            // Validate that we have at least one area of expertise and language
            if (empty($areasOfExpertise)) {
                throw new \Exception('Invalid areas of expertise provided.');
            }

            if (empty($languages)) {
                throw new \Exception('Invalid languages provided.');
            }

            // Step 5: Create astrologer profile record
            $astrologer = Astrologer::create([
                'user_id' => $user->id,
                'years_of_experience' => $validated['years_of_experience'],
                'areas_of_expertise' => $areasOfExpertise,
                'languages' => $languages,
                'profile_photo' => $uploadedFiles['profile_photo'],
                'bio' => $validated['bio'] ?? null,
                'id_proof' => $uploadedFiles['id_proof'],
                'certificate' => $uploadedFiles['certificate'],
                'id_proof_number' => $validated['id_proof_number'],
                'date_of_birth' => $validated['date_of_birth'],
                'status' => 'pending', // Initial status is pending
            ]);

            // Generate Sanctum personal access token (plain token returned to client)
            $plainToken = $user->createToken('auth_token')->plainTextToken;

            // Commit transaction if all operations are successful
            DB::commit();

            // Load relations and prepare full data
            $user->load('astrologer');

            // Add notification to astrologer user about signup.
            NotificationHelper::send(
                $user->id,
                'Signup successful',
                'Your astrologer account has been created and is under review.',
                ['astrologer_id' => $astrologer->id]
            );

            // Return success response with full user + astrologer data and token (Bearer)
            return response()->json([
                'status' => 'success',
                'message' => 'Astrologer signup successful. Your profile is under review.',
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'data' => [
                    'user' => $user,
                    'astrologer' => $astrologer,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Rollback transaction on validation error
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Rollback transaction on any exception
            DB::rollBack();

            // Log the error
            Log::error('Astrologer signup error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?? 'An error occurred during signup. Please try again.',
            ], 500);
        }
    }

    /**
     * Send OTP to astrologer (creates OTP record).
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $phone = $request->input('phone');

        $user = User::where('phone', $phone)->where('user_type', 'astrologer')->first();

        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer with this phone not found.'], 404);
        }

        $astrologer = $user->astrologer;

        // $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $otp = '1234';
        $astrologer->otp = $otp;
        $astrologer->otp_expires_at = Carbon::now()->addMinutes(10);
        $astrologer->otp_verified_at = null;
        $astrologer->save();

        NotificationHelper::send(
            $user->id,
            'OTP generated',
            'A new OTP code has been created for astrologer login.',
            ['phone' => $phone]
        );

        // For development/testing (no external SMS), return OTP in response.
        return response()->json([
            'status' => 'success',
            'message' => 'OTP generated and saved.',
            'data' => [
                'phone' => $phone,
                'otp' => $otp,
                'expires_at' => $astrologer->otp_expires_at,
            ],
        ], 200);
    }

    /**
     * Verify OTP and issue token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
            'otp' => ['required', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $phone = $request->input('phone');
        $otp = $request->input('otp');

        $user = User::where('phone', $phone)->where('user_type', 'astrologer')->first();

        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer with this phone not found.'], 404);
        }

        $astrologer = $user->astrologer;

        if (!$astrologer->otp || !$astrologer->otp_expires_at || Carbon::now()->gt($astrologer->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired or not generated.'], 422);
        }

        if ($astrologer->otp !== $otp) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 422);
        }

        // OTP verified
        $astrologer->otp = null;
        $astrologer->otp_expires_at = null;
        $astrologer->otp_verified_at = Carbon::now();
        $astrologer->save();

        NotificationHelper::send(
            $user->id,
            'OTP verified',
            'You have successfully verified your OTP and are now logged in as astrologer.',
            ['phone' => $phone]
        );

        // Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load('astrologer');

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified.',
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => [
                'user' => $user,
                'astrologer' => $astrologer,
            ],
        ], 200);
    }

    /**
     * Resend OTP (regenerate).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        // For now, same as sendOtp logic
        return $this->sendOtp($request);
    }

    /**
     * Get astrologer profile by user ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getProfile($userId): JsonResponse
    {
        try {
            // Eager load astrologer profile and related skill + other details
            $user = User::with(['astrologer.skill', 'astrologer.otherDetails'])->find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            if ($user->user_type !== 'astrologer' || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This user is not an astrologer.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'astrologer' => $user->astrologer,
                    'skill' => $user->astrologer->skill ?? null,
                    'other_details' => $user->astrologer->otherDetails ?? null,
                    // convenience top-level values
                    'website_link' => optional($user->astrologer->otherDetails)->website_link,
                    'instagram_username' => optional($user->astrologer->otherDetails)->instagram_username,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get profile error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching profile.',
            ], 500);
        }
    }

    public function updateProfilePhoto(UpdateAstrologerProfilePhotoRequest $request): JsonResponse
    {
        $user = $request->user();

        // Ensure astrologer profile exists
        if (!$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;

        // Store new profile photo
        $file = $request->file('profile_photo');
        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'No profile_photo file was uploaded. Make sure you send a multipart/form-data request.',
            ], 422);
        }

        $filename = time() . '_' . $user->id . '_profile_photo.' . $file->getClientOriginalExtension();
        $path = 'astrologers/' . $user->id . '/profile_photo';

        // Delete existing file if present
        if ($astrologer->profile_photo && Storage::disk('public')->exists($astrologer->profile_photo)) {
            Storage::disk('public')->delete($astrologer->profile_photo);
        }

        $storedPath = Storage::disk('public')->putFileAs($path, $file, $filename);
        $astrologer->profile_photo = $storedPath;
        $astrologer->save();

        $user->load('astrologer');

        NotificationHelper::send(
            $user->id,
            'Profile photo updated',
            'Your astrologer profile photo has been updated successfully.',
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profile photo updated successfully.',
            'data' => [
                'user' => $user,
                'astrologer' => $astrologer,
            ],
        ], 200);
    }

    /**
     * Update authenticated astrologer profile (basic info + documents).
     *
     * @param UpdateAstrologerProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateAstrologerProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Update user fields
            $user->fill([
                'name' => $validated['full_name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'phone' => $validated['phone'] ?? $user->phone,
                'city' => $validated['city'] ?? $user->city,
                'country' => $validated['country'] ?? $user->country,
            ]);
            $user->save();

            // Update astrologer fields
            if (isset($validated['id_proof_number'])) {
                $astrologer->id_proof_number = $validated['id_proof_number'];
            }
            if (isset($validated['date_of_birth'])) {
                $astrologer->date_of_birth = $validated['date_of_birth'];
            }

            // Handle optional file uploads
            $fileFields = ['profile_photo', 'id_proof', 'certificate'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = time() . '_' . $user->id . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $path = 'astrologers/' . $user->id . '/' . $field;

                    // Delete old file if exists
                    if ($astrologer->{$field} && Storage::disk('public')->exists($astrologer->{$field})) {
                        Storage::disk('public')->delete($astrologer->{$field});
                    }

                    $astrologer->{$field} = Storage::disk('public')->putFileAs($path, $file, $filename);
                }
            }

            $astrologer->save();
            DB::commit();

            $user->load('astrologer');

            NotificationHelper::send(
                $user->id,
                'Profile updated',
                'Your astrologer profile has been updated successfully.',
                []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $user,
                    'astrologer' => $astrologer,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update profile error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the profile.',
            ], 500);
        }
    }

    /**
     * Get the astrologer home status (availability + pricing) for the authenticated astrologer.
     *
     * @return JsonResponse
     */
    public function getHomeStatus(): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;

        return response()->json([
            'status' => 'success',
            'data' => [
                'astrologer' => $astrologer->only([
                    'chat_enabled',
                    'call_enabled',
                    'video_call_enabled',
                    'chat_rate_per_minute',
                    'call_rate_per_minute',
                    'video_call_rate_per_minute',
                    'po_at_5_enabled',
                    'po_at_5_rate_per_minute',
                    'po_at_5_sessions',
                    'updated_at',
                ]),
            ],
        ], 200);
    }

    /**
     * Update astrologer home status and pricing toggles.
     *
     * @param UpdateAstrologerHomeRequest $request
     * @return JsonResponse
     */
    public function updateHomeStatus(UpdateAstrologerHomeRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $validated = $request->validated();

        // Update only fields provided by the request
        $astrologer->fill($validated);
        $astrologer->save();

        NotificationHelper::send(
            $user->id,
            'Home status updated',
            'Your home status and pricing settings have been updated.',
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Home status updated successfully.',
            'data' => [
                'astrologer' => $astrologer,
            ],
        ], 200);
    }

    /**
     * Add astrologer phone number and send OTP.
     */
    public function addPhoneNumber(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $validated = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $astrologer = $user->astrologer;

        $exists = AstrologerPhoneNumber::where('astrologer_id', $astrologer->id)
            ->where('country_code', $validated['country_code'])
            ->where('phone', $validated['phone'])
            ->first();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'Number already exists.'], 409);
        }

        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiry = now()->addMinutes(10);

        $phoneRecord = AstrologerPhoneNumber::create([
            'astrologer_id' => $astrologer->id,
            'country_code' => $validated['country_code'],
            'phone' => $validated['phone'],
            'is_verified' => false,
            'is_default' => false,
            'otp' => $otp,
            'otp_expires_at' => $expiry,
        ]);

        NotificationHelper::send(
            $user->id,
            'Phone OTP sent',
            "OTP sent to {$validated['country_code']} {$validated['phone']}.",
            ['phone_number_id' => $phoneRecord->id]
        );

        return response()->json(['status' => 'success', 'data' => ['phone' => $phoneRecord]], 201);
    }

    /**
     * Verify phone number OTP.
     */
    public function verifyPhoneNumber(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:4'],
        ]);

        $phoneRecord = AstrologerPhoneNumber::where('id', $id)
            ->where('astrologer_id', $user->astrologer->id)
            ->first();

        if (!$phoneRecord) {
            return response()->json(['status' => 'error', 'message' => 'Phone number not found.'], 404);
        }

        if ($phoneRecord->is_verified) {
            return response()->json(['status' => 'success', 'message' => 'Phone number already verified.', 'data' => ['phone' => $phoneRecord]], 200);
        }

        if (!$phoneRecord->otp || !$phoneRecord->otp_expires_at || now()->greaterThan($phoneRecord->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired. Please request a new one.'], 422);
        }

        if ($phoneRecord->otp !== $validated['otp']) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 422);
        }

        $phoneRecord->update([
            'is_verified' => true,
            'otp' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => now(),
        ]);

        NotificationHelper::send(
            $user->id,
            'Phone verified',
            "Phone number {$phoneRecord->country_code} {$phoneRecord->phone} has been verified.",
            ['phone_number_id' => $phoneRecord->id]
        );

        return response()->json(['status' => 'success', 'message' => 'Phone number verified.', 'data' => ['phone' => $phoneRecord]], 200);
    }

    /**
     * Set a verified phone number as default.
     */
    public function setDefaultPhoneNumber(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $phoneRecord = AstrologerPhoneNumber::where('id', $id)
            ->where('astrologer_id', $user->astrologer->id)
            ->first();

        if (!$phoneRecord) {
            return response()->json(['status' => 'error', 'message' => 'Phone number not found.'], 404);
        }

        if (!$phoneRecord->is_verified) {
            return response()->json(['status' => 'error', 'message' => 'Phone number not verified.'], 422);
        }

        AstrologerPhoneNumber::where('astrologer_id', $user->astrologer->id)->update(['is_default' => false]);

        $phoneRecord->update(['is_default' => true]);

        NotificationHelper::send(
            $user->id,
            'Default number changed',
            "{$phoneRecord->country_code} {$phoneRecord->phone} is now your default number.",
            ['phone_number_id' => $phoneRecord->id]
        );

        return response()->json(['status' => 'success', 'message' => 'Default phone number set.', 'data' => ['phone' => $phoneRecord]], 200);
    }

    /**
     * Get all phone numbers for authenticated astrologer.
     */
    public function getPhoneNumbers(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $numbers = AstrologerPhoneNumber::where('astrologer_id', $user->astrologer->id)->orderByDesc('is_default')->orderBy('id')->get();

        return response()->json(['status' => 'success', 'data' => ['numbers' => $numbers]], 200);
    }

    /**
     * Get astrologer bank accounts for authenticated astrologer.
     */
    public function getBankAccounts(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $accounts = AstrologerBankAccount::where('astrologer_id', $user->astrologer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['status' => 'success', 'data' => ['bank_accounts' => $accounts]], 200);
    }

    /**
     * Get astrologer availability schedule.
     */
    public function getAvailability(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $availability = $user->astrologer->availability ?? [];

        return response()->json(['status' => 'success', 'data' => ['availability' => $availability]], 200);
    }

    /**
     * Set astrologer availability schedule.
     */
    public function setAvailability(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $payload = $request->input('availability');

        if (!is_array($payload)) {
            return response()->json(['status' => 'error', 'message' => 'availability must be an array.'], 422);
        }

        $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $daySet = [];
        foreach ($payload as $entry) {
            if (!is_array($entry)) {
                return response()->json(['status' => 'error', 'message' => 'Each availability item must be an object.'], 422);
            }

            $day = isset($entry['day']) ? strtolower(trim($entry['day'])) : null;
            if (!$day || !in_array($day, $allowedDays, true)) {
                return response()->json(['status' => 'error', 'message' => "Invalid day provided: {$day}"], 422);
            }

            if (in_array($day, $daySet, true)) {
                return response()->json(['status' => 'error', 'message' => "Duplicate day entry: {$day}"], 422);
            }
            $daySet[] = $day;

            $enabled = $entry['enabled'] ?? false;
            if (!is_bool($enabled) && !in_array($enabled, [0, 1, '0', '1'], true)) {
                return response()->json(['status' => 'error', 'message' => "enabled must be boolean for day: {$day}"], 422);
            }
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            $slots = $entry['slots'] ?? [];

            if ($enabled && !is_array($slots)) {
                return response()->json(['status' => 'error', 'message' => "slots must be an array when enabled is true for day: {$day}"], 422);
            }

            if ($enabled) {
                if (count($slots) === 0) {
                    return response()->json(['status' => 'error', 'message' => "At least 1 slot required for enabled day: {$day}"], 422);
                }

                foreach ($slots as $slot) {
                    if (!is_array($slot)) {
                        return response()->json(['status' => 'error', 'message' => "Each slot must be an object for day: {$day}"], 422);
                    }

                    if (empty($slot['start']) || empty($slot['end'])) {
                        return response()->json(['status' => 'error', 'message' => "Each slot must have start and end for day: {$day}"], 422);
                    }

                    $start = $slot['start'];
                    $end = $slot['end'];

                    $startTime = DateTime::createFromFormat('H:i', $start);
                    $endTime = DateTime::createFromFormat('H:i', $end);

                    if (!$startTime || !$endTime) {
                        return response()->json(['status' => 'error', 'message' => "Invalid time format for slot ({$start} - {$end}) on day: {$day}, expected HH:MM"], 422);
                    }

                    if ($startTime >= $endTime) {
                        return response()->json(['status' => 'error', 'message' => "Slot start must be before end for day: {$day} ({$start} >= {$end})"], 422);
                    }
                }
            }
        }

        // Normalize payload with canonical day order and fills missing days as disabled
        $availability = [];
        foreach ($allowedDays as $dayKey) {
            $existing = array_values(array_filter($payload, fn($item) => isset($item['day']) && strtolower($item['day']) === $dayKey));
            if (count($existing) > 0) {
                $item = $existing[0];
                $enabled = filter_var($item['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $slots = $enabled ? array_map(function ($slot) {
                    return [
                        'start' => $slot['start'],
                        'end' => $slot['end'],
                    ];
                }, $item['slots'] ?? []) : [];
            } else {
                $enabled = false;
                $slots = [];
            }

            $availability[] = [
                'day' => $dayKey,
                'enabled' => $enabled,
                'slots' => $slots,
            ];
        }

        $astrologer = $user->astrologer;
        $astrologer->availability = $availability;
        $astrologer->save();

        return response()->json(['status' => 'success', 'message' => 'Availability updated successfully.', 'data' => ['availability' => $availability]], 200);
    }

    /**
     * Delete a single availability slot for an astrologer.
     */
    public function deleteAvailability(Request $request, string $day, int $slotIndex): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $day = strtolower(trim($day));
        $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Validate day
        if (!in_array($day, $allowedDays, true)) {
            return response()->json(['status' => 'error', 'message' => "Invalid day provided: {$day}"], 422);
        }

        // Validate slotIndex is non-negative
        if ($slotIndex < 0) {
            return response()->json(['status' => 'error', 'message' => 'Slot index must be a non-negative integer.'], 422);
        }

        $astrologer = $user->astrologer;
        $availability = $astrologer->availability ?? [];

        // Find the day in availability array
        $dayIndex = null;
        foreach ($availability as $index => $item) {
            if ($item['day'] === $day) {
                $dayIndex = $index;
                break;
            }
        }

        if ($dayIndex === null) {
            return response()->json(['status' => 'error', 'message' => "Availability not found for day: {$day}"], 404);
        }

        $dayItem = $availability[$dayIndex];

        // Check if slot index exists
        if (!isset($dayItem['slots'][$slotIndex])) {
            return response()->json(['status' => 'error', 'message' => "Slot index {$slotIndex} not found for day: {$day}"], 404);
        }

        // Remove the slot
        unset($availability[$dayIndex]['slots'][$slotIndex]);

        // Reindex the slots array
        $availability[$dayIndex]['slots'] = array_values($availability[$dayIndex]['slots']);

        // If no slots left, disable the day
        if (count($availability[$dayIndex]['slots']) === 0) {
            $availability[$dayIndex]['enabled'] = false;
        }

        // Save updated availability
        $astrologer->availability = $availability;
        $astrologer->save();

        return response()->json([
            'status' => 'success',
            'message' => "Slot {$slotIndex} deleted successfully for day: {$day}",
            'data' => ['availability' => $availability]
        ], 200);
    }

    /**
     * Get astrologer sleep hours settings.
     */
    public function getSleepHours(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $astrologer = $user->astrologer;

        return response()->json([
            'status' => 'success',
            'data' => [
                'sleep_start_time' => $astrologer->sleep_start_time ? $astrologer->sleep_start_time->format('H:i') : null,
                'sleep_end_time' => $astrologer->sleep_end_time ? $astrologer->sleep_end_time->format('H:i') : null,
                'sleep_duration_minutes' => $astrologer->sleep_duration_minutes,
            ],
        ], 200);
    }

    /**
     * Set astrologer sleep hours.
     */
    public function setSleepHours(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $validated = $request->validate([
            'sleep_start_time' => 'required|date_format:H:i',
            'sleep_end_time' => 'required|date_format:H:i',
        ]);

        $startTime = DateTime::createFromFormat('H:i', $validated['sleep_start_time']);
        $endTime = DateTime::createFromFormat('H:i', $validated['sleep_end_time']);

        if (!$startTime || !$endTime) {
            return response()->json(['status' => 'error', 'message' => 'Invalid time format. Use HH:MM (24h).'], 422);
        }

        $duration = ($endTime->format('U') - $startTime->format('U')) / 60;
        if ($duration <= 0) {
            $duration = (($endTime->format('U') + 24 * 3600) - $startTime->format('U')) / 60;
        }

        if ($duration > 12 * 60) {
            return response()->json(['status' => 'error', 'message' => 'Sleep duration should not exceed 12 hours.'], 422);
        }

        $astrologer = $user->astrologer;
        $astrologer->sleep_start_time = $validated['sleep_start_time'];
        $astrologer->sleep_end_time = $validated['sleep_end_time'];
        $astrologer->sleep_duration_minutes = (int) $duration;
        $astrologer->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Sleep hours updated successfully.',
            'data' => [
                'sleep_start_time' => $astrologer->sleep_start_time->format('H:i'),
                'sleep_end_time' => $astrologer->sleep_end_time->format('H:i'),
                'sleep_duration_minutes' => $astrologer->sleep_duration_minutes,
            ],
        ], 200);
    }

    /**
     * Logout astrologer (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $token = $request->user()->currentAccessToken();
        if ($token) {
            /** @var \Laravel\Sanctum\PersonalAccessToken $token */
            $token->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Logged out successfully.'], 200);
    }

    /**
     * Delete astrologer account and all related data.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // Revoke all tokens
            $user->tokens()->delete();

            // Delete the user, cascading via FK to astrologer + child records
            $user->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Astrologer account deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Astrologer delete account error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete astrologer account.'], 500);
        }
    }

    /**
     * Add astrologer bank account.
     */
    public function addBankAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:150'],
            'bank_name' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'size:11'],
            'passbook_document' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $astrologer = $user->astrologer;

        $exists = AstrologerBankAccount::where('astrologer_id', $astrologer->id)
            ->where('account_number', $validated['account_number'])
            ->where('bank_name', $validated['bank_name'])
            ->first();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'This bank account already exists.'], 409);
        }

        $passbookPath = null;
        if ($request->hasFile('passbook_document')) {
            $file = $request->file('passbook_document');
            $filename = time() . '_passbook_' . $user->id . '.' . $file->getClientOriginalExtension();
            $passbookPath = Storage::disk('public')->putFileAs('astrologers/' . $user->id . '/bank_accounts', $file, $filename);
        }

        // If no default selected and this is first account, make default
        $isDefault = !AstrologerBankAccount::where('astrologer_id', $astrologer->id)->exists();

        if ($isDefault) {
            AstrologerBankAccount::where('astrologer_id', $astrologer->id)->update(['is_default' => false]);
        }

        $account = AstrologerBankAccount::create([
            'astrologer_id' => $astrologer->id,
            'account_holder_name' => $validated['account_holder_name'],
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'ifsc_code' => strtoupper($validated['ifsc_code']),
            'passbook_document' => $passbookPath,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Bank account added successfully.', 'data' => ['bank_account' => $account]], 201);
    }

    /**
     * Set astrologer bank account as default.
     */
    public function setDefaultBankAccount(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
        }

        $account = AstrologerBankAccount::where('id', $id)->where('astrologer_id', $user->astrologer->id)->first();

        if (!$account) {
            return response()->json(['status' => 'error', 'message' => 'Bank account not found.'], 404);
        }

        AstrologerBankAccount::where('astrologer_id', $user->astrologer->id)->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return response()->json(['status' => 'success', 'message' => 'Default bank account set successfully.', 'data' => ['bank_account' => $account]], 200);
    }

    /**
     * Store or update astrologer skill details.
     *
     * @param UpdateAstrologerSkillRequest $request
     * @return JsonResponse
     */
    public function updateSkill(UpdateAstrologerSkillRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $validated = $request->validated();

        $skill = AstrologerSkill::updateOrCreate(
            ['astrologer_id' => $astrologer->id],
            [
                'category' => $validated['category'] ?? null,
                'primary_skills' => $validated['primary_skills'] ?? null,
                'all_skills' => $validated['all_skills'] ?? null,
                'languages' => $validated['languages'] ?? null,
                'experience_years' => $validated['experience_years'] ?? null,
                'daily_contribution_hours' => $validated['daily_contribution_hours'] ?? null,
                'heard_about' => $validated['heard_about'] ?? null,
            ]
        );

        NotificationHelper::send(
            $user->id,
            'Skill updated',
            'Your astrologer skills have been saved successfully.',
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Skill details saved successfully.',
            'data' => [
                'skill' => $skill,
            ],
        ], 200);
    }

    /**
     * Store or update astrologer other details.
     *
     * @param UpdateAstrologerOtherDetailsRequest $request
     * @return JsonResponse
     */
    public function updateOtherDetails(UpdateAstrologerOtherDetailsRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $validated = $request->validated();

        $otherDetails = AstrologerOtherDetail::updateOrCreate(
            ['astrologer_id' => $astrologer->id],
            [
                'gender' => $validated['gender'] ?? null,
                'current_address' => $validated['current_address'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'website_link' => $validated['website_link'] ?? null,
                'instagram_username' => $validated['instagram_username'] ?? null,
            ]
        );

        NotificationHelper::send(
            $user->id,
            'Profile details updated',
            'Your astrologer profile other details have been updated successfully.',
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Other details saved successfully.',
            'data' => [
                'other_details' => $otherDetails,
            ],
        ], 200);
    }

    /**
     * Get logged in astrologer's followers (community) with count.
     */
    public function getFollowers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'query' => 'nullable|string|min:1|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $astrologer = $user->astrologer;
            $searchQuery = $request->input('query');
            $perPage = $request->input('per_page', 15);

            $query = AstrologerCommunity::with('user')
                ->where('astrologer_id', $astrologer->id);

            // Apply search filter if provided
            if ($searchQuery) {
                $query->whereHas('user', function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('phone', 'LIKE', "%{$searchQuery}%");
                });
            }

            // Apply pagination
            $results = $query->orderByDesc('created_at')->paginate($perPage);

            $data = $results->map(function ($record) {
                return [
                    'user_id' => $record->user?->id,
                    'name' => $record->user?->name,
                    'email' => $record->user?->email,
                    'phone' => $record->user?->phone,
                    'is_liked' => $record->is_liked,
                    'liked_at' => $record->liked_at,
                    'followed_at' => $record->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Followers retrieved successfully',
                'data' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'followers' => $data,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Get followers error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve followers: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle like/unlike on a follower.
     */
    public function toggleFollowerLike(Request $request, $userId): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $follower = User::find($userId);

        if (!$follower) {
            return response()->json([
                'status' => 'error',
                'message' => 'Follower user not found.',
            ], 404);
        }

        $community = AstrologerCommunity::firstOrNew([
            'astrologer_id' => $astrologer->id,
            'user_id' => $follower->id,
        ]);

        $community->is_liked = !$community->is_liked;
        $community->liked_at = $community->is_liked ? Carbon::now() : null;
        $community->save();

        NotificationHelper::send(
            $follower->id,
            $community->is_liked ? 'You were liked' : 'You were unliked',
            $community->is_liked ? "Astrologer {$user->name} liked you." : "Astrologer {$user->name} unliked you.",
            ['astrologer_id' => $astrologer->id]
        );

        return response()->json([
            'status' => 'success',
            'message' => $community->is_liked ? 'Follower liked.' : 'Follower unliked.',
            'data' => [
                'user_id' => $follower->id,
                'is_liked' => $community->is_liked,
                'liked_at' => $community->liked_at,
            ],
        ], 200);
    }

    /**
     * Get favorite (liked) followers for logged in astrologer.
     */
    public function getFavorites(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->astrologer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Astrologer profile not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'query' => 'nullable|string|min:1|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $astrologer = $user->astrologer;
            $searchQuery = $request->input('query');
            $perPage = $request->input('per_page', 15);

            $query = AstrologerCommunity::with('user')
                ->where('astrologer_id', $astrologer->id)
                ->where('is_liked', true);

            // Apply search filter if provided
            if ($searchQuery) {
                $query->whereHas('user', function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('phone', 'LIKE', "%{$searchQuery}%");
                });
            }

            // Apply pagination
            $results = $query->orderByDesc('liked_at')->paginate($perPage);

            $data = $results->map(function ($record) {
                return [
                    'user_id' => $record->user?->id,
                    'name' => $record->user?->name,
                    'email' => $record->user?->email,
                    'phone' => $record->user?->phone,
                    'liked_at' => $record->liked_at,
                    'followed_at' => $record->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Favorites retrieved successfully',
                'data' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'favorites' => $data,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Get favorites error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve favorites: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle astrologer online/offline status or specific service status.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * Query/Body Parameters:
     * - type: (optional) 'chat', 'call', or 'video_call'
     *   - If not provided: toggles is_online status
     *   - type=chat: toggles chat_enabled status
     *   - type=call: toggles call_enabled status
     *   - type=video_call: toggles video_call_enabled status
     */
    public function toggleOnlineStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $type = $request->input('type'); // Get type from query or body

        // Determine which field to toggle based on type
        if ($type === 'chat') {
            // Toggle chat_enabled status
            $astrologer->is_chat_enabled = !$astrologer->is_chat_enabled;
            $fieldName = 'is_chat_enabled';
            $displayName = 'Chat';
        } elseif ($type === 'call') {
            // Toggle call_enabled status
            $astrologer->is_call_enabled = !$astrologer->is_call_enabled;
            $fieldName = 'is_call_enabled';
            $displayName = 'Call';
        } elseif ($type === 'video_call') {
            // Toggle video_call_enabled status
            $astrologer->is_video_call_enabled = !$astrologer->is_video_call_enabled;
            $fieldName = 'is_video_call_enabled';
            $displayName = 'Video Call';
        } else {
            // Default: Toggle is_online status (when type is not provided)
            $astrologer->is_online = !$astrologer->is_online;
            $fieldName = 'is_online';
            $displayName = 'Online';
        }
        
        // echo'<pre>';print_r($astrologer);die;

        $astrologer->save();

        NotificationHelper::send(
            $user->id,
            "{$displayName} status updated",
            "Your {$displayName} status has been " . ($astrologer->$fieldName ? 'enabled' : 'disabled') . '.',
            [$fieldName => $astrologer->$fieldName]
        );

        // Prepare response data
        $responseData = [
            'astrologer_id' => $astrologer->id,
            $fieldName => (bool) $astrologer->$fieldName,
            'updated_at' => $astrologer->updated_at,
        ];

        return response()->json([
            'status' => 'success',
            'message' => "{$displayName} status updated successfully.",
            'data' => $responseData,
        ], 200);
    }
}
