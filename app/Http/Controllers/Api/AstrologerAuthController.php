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
use App\Models\AstrologerSkill;
use App\Models\AstrologerOtherDetail;
use App\Models\AstrologerCommunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

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
                        \Log::error("File upload error for {$field}: " . $e->getMessage());
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
            \Log::error('Astrologer signup error: ' . $e->getMessage());

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
            $user = User::with('astrologer')->find($userId);

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
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Get profile error: ' . $e->getMessage());
            
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
            \Log::error('Update profile error: ' . $e->getMessage());

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
        $user = auth()->user();

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

        return response()->json([
            'status' => 'success',
            'message' => 'Home status updated successfully.',
            'data' => [
                'astrologer' => $astrologer,
            ],
        ], 200);
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
        $user = $request->user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;

        $query = AstrologerCommunity::with('user')->where('astrologer_id', $astrologer->id);
        $count = $query->count();
        $followers = $query->get();

        $data = $followers->map(function ($record) {
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
            'count' => $count,
            'data' => $data,
        ], 200);
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
        $user = $request->user();

        if (!$user || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer profile not found.',
            ], 404);
        }

        $astrologer = $user->astrologer;
        $favorites = AstrologerCommunity::with('user')
            ->where('astrologer_id', $astrologer->id)
            ->where('is_liked', true)
            ->get();

        $data = $favorites->map(function ($record) {
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
            'count' => $data->count(),
            'data' => $data,
        ], 200);
    }
}
