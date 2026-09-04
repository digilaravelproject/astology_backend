<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfilePhotoRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerReview;
use App\Models\AppNotification;
use App\Models\MatrimonyProfile;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\NotificationHelper;
use App\Services\BlockService;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Exception;
use Throwable;

class UserAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SECTION 1: AUTHENTICATION & ACCESS CONTROL
    |--------------------------------------------------------------------------
    | Handles consumer OTP login, registration, verification with rate-limits,
    | single active session management, permanent device token deletion on
    | logout, and account removal.
    */

    /**
     * Send OTP to consumer user (creates account if doesn't exist) with 30s cooldown.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $phone = $request->input('phone');

            // 1. Strict Role Boundary Check: Disallow Astrologers from logging in via Consumer App
            $existingAstrologer = User::where('phone', $phone)
                ->where('user_type', 'astrologer')
                ->first();

            if ($existingAstrologer) {
                DB::rollBack();
                return response()->json([
                    'status'     => 'error',
                    'message'    => 'This phone number is registered as an Astrologer. Please log in using the Astrologer App.',
                    'error_code' => 'ROLE_MISMATCH_ASTROLOGER'
                ], 403);
            }

            // 2. 30-Second Resend Cooldown Check
            $cooldownKey = "otp_cooldown:{$phone}";
            if (Cache::has($cooldownKey)) {
                $lastSent = (int) Cache::get($cooldownKey);
                $diff = time() - $lastSent;
                if ($diff < 30) {
                    $retryAfter = 30 - $diff;
                    DB::rollBack();
                    return response()->json([
                        'status'              => 'error',
                        'message'             => "Please wait {$retryAfter} seconds before requesting a new OTP.",
                        'error_code'          => 'OTP_COOLDOWN_ACTIVE',
                        'retry_after_seconds' => $retryAfter,
                    ], 429);
                }
            }

            // 3. Check if consumer user exists (pessimistic lock for atomic safety)
            $user = User::where('phone', $phone)
                ->where('user_type', 'user')
                ->lockForUpdate()
                ->first();

            if (!$user) {
                $user = User::create([
                    'name'      => $phone,
                    'phone'     => $phone,
                    'user_type' => 'user',
                    'password'  => bcrypt($phone),
                ]);
            }

            if (in_array($phone, ['7458086472', '9651017054','7303838972'])) {
                $otp = '1234';
            } else {
                $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }

            // Store OTP in users table
            $user->otp = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->otp_verified_at = null;
            $user->save();

            DB::commit();

            // Set cooldown for 30 seconds & reset failed attempts on new OTP request
            Cache::put($cooldownKey, time(), 30);
            Cache::forget("otp_attempts:{$phone}");

            // Asynchronously dispatch SMS OTP
            \App\Jobs\SendSmsOtpJob::dispatch($phone, $otp);

            NotificationHelper::send(
                $user->id,
                'OTP generated',
                "A new OTP code was generated for your login.",
                ['phone' => $phone]
            );

            $exposedOtp = (!app()->isProduction() && config('app.debug')) ? $otp : null;

            return response()->json([
                'status'  => 'success',
                'message' => 'OTP generated and saved.',
                'data'    => [
                    'phone'      => $phone,
                    'user_id'    => $user->id,
                    'otp'        => $exposedOtp,
                    'expires_at' => $user->otp_expires_at,
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User sendOtp error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while sending OTP.',
            ], 500);
        }
    }

    /**
     * Verify OTP, lock on 5 failed attempts, and issue scoped Sanctum token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
            'otp'   => ['required', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $phone = $request->input('phone');
            $otp = $request->input('otp');

            // 1. Strict Role Boundary Check
            $existingAstrologer = User::where('phone', $phone)
                ->where('user_type', 'astrologer')
                ->first();

            if ($existingAstrologer) {
                DB::rollBack();
                return response()->json([
                    'status'     => 'error',
                    'message'    => 'This phone number is registered as an Astrologer. Please log in using the Astrologer App.',
                    'error_code' => 'ROLE_MISMATCH_ASTROLOGER'
                ], 403);
            }

            // 2. Max 5 Wrong Attempts Check
            $attemptKey = "otp_attempts:{$phone}";
            $attempts = (int) Cache::get($attemptKey, 0);

            if ($attempts >= 5) {
                DB::rollBack();
                return response()->json([
                    'status'              => 'error',
                    'message'             => 'Too many invalid OTP attempts. Please wait 10 minutes or request a new OTP.',
                    'error_code'          => 'MAX_OTP_ATTEMPTS_EXCEEDED',
                    'retry_after_seconds' => 600,
                ], 429);
            }

            $user = User::where('phone', $phone)
                ->where('user_type', 'user')
                ->lockForUpdate()
                ->first();

            if (!$user) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }

            $isTestUser = in_array($phone, ['7458086472', '9651017054','7303838972'8]);

            if (!($isTestUser && $otp === '1234')) {
                if (!$user->otp || !$user->otp_expires_at || Carbon::now()->gt($user->otp_expires_at)) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'OTP expired or not generated.'], 422);
                }

                if ($user->otp !== $otp) {
                    $attempts = (int) Cache::increment($attemptKey);
                    if ($attempts === 1) {
                        Cache::put($attemptKey, 1, now()->addMinutes(10));
                    }
                    $remaining = max(0, 5 - $attempts);

                    DB::rollBack();
                    return response()->json([
                        'status'             => 'error',
                        'message'            => "Invalid OTP. You have {$remaining} attempt(s) remaining.",
                        'error_code'         => 'INVALID_OTP',
                        'remaining_attempts' => $remaining,
                    ], 422);
                }
            }

            // OTP verified
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_verified_at = Carbon::now();
            $user->save();

            // Clear cooldown and attempts cache on successful verification
            Cache::forget($attemptKey);
            Cache::forget("otp_cooldown:{$phone}");

            // Revoke all existing tokens and old devices for single active session constraint
            $user->tokens()->delete();
            UserDevice::where('user_id', $user->id)->delete();
            $user->fcm_token = null;
            $user->save();

            // Issue Sanctum token scoped specifically for 'role:user'
            $token = $user->createToken('user_token', ['role:user'])->plainTextToken;

            // Broadcast force logout over WebSocket to instantly terminate old devices
            try {
                broadcast(new \App\Events\UserForceLoggedOut($user->id, 'logged_in_on_another_device', (string) $request->input('device_id', '')));
            } catch (Throwable $e) {
                // Ignore broadcast error
            }

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'OTP verified',
                'You have successfully verified your OTP and are now logged in.',
                ['phone' => $phone]
            );

            return response()->json([
                'status'     => 'success',
                'message'    => 'OTP verified.',
                'token'      => $token,
                'token_type' => 'Bearer',
                'data'       => [
                    'user' => $user,
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User verifyOtp error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to verify OTP.'], 500);
        }
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    /**
     * Logout consumer user by revoking all tokens and permanently deleting device records.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            $fcmToken = $request->input('fcm_token');
            $deviceId = $request->input('device_id');

            // Delete device token(s) on logout
            if ($deviceId || $fcmToken) {
                $query = UserDevice::where('user_id', $user->id);
                if ($deviceId) {
                    $query->where('device_id', $deviceId);
                } elseif ($fcmToken) {
                    $query->where('fcm_token', $fcmToken);
                }
                $query->delete();
            } else {
                UserDevice::where('user_id', $user->id)->delete();
            }

            if (!$fcmToken || $user->fcm_token === $fcmToken) {
                $user->fcm_token = null;
                $user->save();
            }

            // Revoke tokens for the user
            $user->tokens()->delete();

            try {
                app(PresenceService::class)->setOffline($user->id);
            } catch (Throwable $e) {
                // Ignore presence offline error on logout
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Logged out successfully.',
                'data'    => [
                    'user_id'       => $user->id,
                    'logged_out_at' => now(),
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('User logout error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while logging out.',
            ], 500);
        }
    }

    /**
     * Delete user account and cascade delete all associated data.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $userId = $user->id;
            $userName = $user->name;

            // Delete wallet transactions
            WalletTransaction::where('wallet_id', function ($query) use ($userId) {
                $query->select('id')->from('wallets')->where('user_id', $userId);
            })->delete();

            // Delete wallet
            Wallet::where('user_id', $userId)->delete();

            // Delete reviews by user
            AstrologerReview::where('user_id', $userId)->delete();

            // Delete astrologer community records
            AstrologerCommunity::where('user_id', $userId)->delete();

            // Delete matrimony profiles
            MatrimonyProfile::where('user_id', $userId)->delete();

            // Delete notifications for user
            AppNotification::where('user_id', $userId)->delete();

            // Delete user devices
            UserDevice::where('user_id', $userId)->delete();

            // Revoke all tokens
            $user->tokens()->delete();

            // Delete user record
            $user->delete();

            DB::commit();

            Log::info("User account deleted: ID={$userId}, Name={$userName}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Account deleted successfully. All your data has been removed.',
                'data'    => [
                    'user_id'    => $userId,
                    'deleted_at' => now(),
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete user account error: ' . $e->getMessage());

            return response()->json([
                'status'        => 'error',
                'message'       => 'An error occurred while deleting the account.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 2: USER PROFILE & MEDIA MANAGEMENT
    |--------------------------------------------------------------------------
    | Handles fetching consumer user profiles, updating personal details
    | (astrological birth data, place, occupation), and avatar uploads.
    */

    /**
     * Get user profile by user ID.
     */
    public function getProfile($userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }

            if ($user->user_type !== 'user') {
                return response()->json(['status' => 'error', 'message' => 'This is not a regular user.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'user' => $user,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Get profile error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An error occurred while fetching profile.'], 500);
        }
    }

    /**
     * Update user profile after OTP verification.
     */
    public function updateProfile(UpdateUserProfileRequest $request, $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }

            if ($user->user_type !== 'user') {
                return response()->json(['status' => 'error', 'message' => 'This is not a regular user account.'], 403);
            }

            if (!$user->otp_verified_at) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Please verify your phone number with OTP before updating profile.',
                ], 403);
            }

            DB::beginTransaction();

            $updateData = [];
            $fields = [
                'name', 'phone', 'email', 'gender', 'date_of_birth',
                'time_of_birth', 'place_of_birth', 'city', 'country',
                'latitude', 'longitude', 'relationship_status',
                'occupation', 'languages'
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $val = $request->input($field);
                    $updateData[$field] = $val !== '' ? $val : null;
                }
            }

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $filename = time() . '_' . $user->id . '_profile_photo.' . $file->getClientOriginalExtension();
                $path = 'users/' . $user->id . '/profile_photo';

                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $updateData['profile_photo'] = Storage::disk('public')->putFileAs($path, $file, $filename);
            }

            $updateData['profile_completed'] = true;

            $user->update($updateData);

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'Profile updated',
                'Your profile has been successfully updated.',
                []
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile updated successfully.',
                'data'    => [
                    'user' => $user->fresh(),
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update user profile error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while updating profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update authenticated user profile photo.
     */
    public function updateProfilePhoto(UpdateUserProfilePhotoRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $file = $request->file('profile_photo');

        if (!$file) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No profile_photo file was uploaded. Make sure you send a multipart/form-data request.',
            ], 422);
        }

        $filename = time() . '_' . $user->id . '_profile_photo.' . $file->getClientOriginalExtension();
        $path = 'users/' . $user->id . '/profile_photo';

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $storedPath = Storage::disk('public')->putFileAs($path, $file, $filename);
        $user->profile_photo = $storedPath;
        $user->save();

        NotificationHelper::send(
            $user->id,
            'Profile photo updated',
            'Your profile photo has been successfully updated.',
            []
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile photo updated successfully.',
            'data'    => [
                'user' => $user->fresh(),
            ],
        ], 200);
    }

    /**
     * Update in-app user profile (authenticated).
     */
    public function updateInAppProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        DB::beginTransaction();

        try {
            $updateData = [];
            $fields = [
                'name', 'phone', 'email', 'gender', 'date_of_birth',
                'time_of_birth', 'place_of_birth', 'city', 'country',
                'latitude', 'longitude', 'relationship_status',
                'occupation', 'languages'
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $val = $request->input($field);
                    $updateData[$field] = $val !== '' ? $val : null;
                }
            }

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $filename = time() . '_' . $user->id . '_profile_photo.' . $file->getClientOriginalExtension();
                $path = 'users/' . $user->id . '/profile_photo';

                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $updateData['profile_photo'] = Storage::disk('public')->putFileAs($path, $file, $filename);
            }

            $updateData['profile_completed'] = true;

            $user->update($updateData);

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'Profile updated',
                'Your profile has been successfully updated.',
                []
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile updated successfully.',
                'data'    => [
                    'user' => $user->fresh(),
                ],
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update in-app profile error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while updating the profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 3: ASTROLOGER FOLLOWING & INTERACTIONS
    |--------------------------------------------------------------------------
    | Allows consumer users to follow, unfollow, and retrieve the list of
    | followed astrologers with live status and ratings.
    */

    /**
     * Get list of astrologers that the authenticated user is following.
     */
    public function getFollowing(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        try {
            $following = AstrologerCommunity::with(['astrologer.user'])
                ->where('user_id', $user->id)
                ->where('is_liked', true)
                ->orderByDesc('liked_at')
                ->get();

            $data = $following->map(function ($record) {
                $astrologer = $record->astrologer;
                
                $avgRating = AstrologerReview::where('astrologer_id', $astrologer->id)->avg('rating');
                $avgRatingValue = $avgRating ? (float) number_format($avgRating, 2) : 0;
                
                $isChatEnabled = (bool) $astrologer->is_chat_enabled;
                $isCallEnabled = (bool) $astrologer->is_call_enabled;
                $isOnline = $isChatEnabled || $isCallEnabled;
                
                return [
                    'astrologer_id'       => $astrologer->id,
                    'name'                => $astrologer->user->name,
                    'email'               => $astrologer->user->email,
                    'phone'               => $astrologer->user->phone,
                    'profile_photo'       => $astrologer->profile_photo,
                    'years_of_experience' => $astrologer->years_of_experience,
                    'areas_of_expertise'  => $astrologer->areas_of_expertise,
                    'languages'           => $astrologer->languages,
                    'bio'                 => $astrologer->bio,
                    'status'              => $astrologer->status,
                    'avg_rating'          => $avgRatingValue,
                    'is_online'           => $isOnline ? 1 : 0,
                    'is_chat_enabled'     => $isChatEnabled ? 1 : 0,
                    'is_call_enabled'     => $isCallEnabled ? 1 : 0,
                    'followed_at'         => $record->liked_at,
                    'created_at'          => $record->created_at,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Following list retrieved successfully.',
                'data'    => [
                    'count'     => $data->count(),
                    'following' => $data,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Get following list error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while retrieving the following list.',
            ], 500);
        }
    }

    /**
     * Follow / unfollow an astrologer (toggle follow state).
     */
    public function toggleFollowAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer not found.'], 404);
        }

        $community = AstrologerCommunity::where('astrologer_id', $astrologer->id)
            ->where('user_id', $user->id)
            ->first();

        if ($community && $community->is_liked) {
            $community->delete();

            NotificationHelper::send(
                $user->id,
                'Astrologer unfollowed',
                "You have unfollowed astrologer {$astrologer->user->name}.",
                ['astrologer_id' => $astrologer->id]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Astrologer unfollowed.',
                'data'    => [
                    'astrologer_id' => $astrologer->id,
                    'is_following'  => false,
                    'followed_at'   => null,
                ],
            ], 200);
        }

        if (!$community) {
            $community = new AstrologerCommunity([
                'astrologer_id' => $astrologer->id,
                'user_id'       => $user->id,
            ]);
        }

        $community->is_liked = true;
        $community->liked_at = Carbon::now();
        $community->save();

        NotificationHelper::send(
            $user->id,
            'Astrologer followed',
            "You are now following astrologer {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id]
        );

        NotificationHelper::send(
            $astrologer->user->id,
            'New follower',
            "{$user->name} has started following you.",
            ['user_id' => $user->id]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Astrologer followed.',
            'data'    => [
                'astrologer_id' => $astrologer->id,
                'is_following'  => true,
                'followed_at'   => $community->liked_at,
            ],
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 4: MODERATION & SAFETY CONTROLS
    |--------------------------------------------------------------------------
    | Allows consumer users to report inappropriate behavior, block/unblock
    | astrologers, and retrieve list of blocked astrologers.
    */

    /**
     * Report an astrologer with a reason.
     */
    public function reportAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer not found.'], 404);
        }

        $community = AstrologerCommunity::firstOrNew([
            'astrologer_id' => $astrologer->id,
            'user_id'       => $user->id,
        ]);

        $community->report_reason = $request->input('reason');
        $community->reported_at = Carbon::now();
        $community->save();

        NotificationHelper::send(
            $user->id,
            'User reported',
            "You have reported the user {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id, 'reason' => $community->report_reason]
        );

        NotificationHelper::send(
            $astrologer->user->id,
            'You were reported',
            "Your account has been reported by user {$user->name}.",
            ['user_id' => $user->id, 'reason' => $community->report_reason]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Reported successfully.',
            'data'    => [
                'astrologer_id' => $astrologer->id,
                'report_reason' => $community->report_reason,
                'reported_at'   => $community->reported_at,
            ],
        ], 200);
    }

    /**
     * Block an astrologer.
     */
    public function blockAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $astrologer = Astrologer::with('user')->find($astrologerId)
            ?? Astrologer::with('user')->where('user_id', $astrologerId)->first();

        if (!$astrologer || !$astrologer->user) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer not found.'], 404);
        }

        $reason = $request->input('reason', $request->input('report_reason'));

        /** @var BlockService $blockService */
        $blockService = app(BlockService::class);
        $userBlock = $blockService->block($user, $astrologer->user, $reason);

        NotificationHelper::send(
            $user->id,
            'User blocked',
            "You have blocked astrologer {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Astrologer blocked successfully.',
            'data'    => [
                'astrologer_id'      => $astrologer->id,
                'astrologer_user_id' => $astrologer->user->id,
                'is_blocked'         => true,
                'blocked_at'         => $userBlock->created_at,
            ],
        ], 200);
    }

    /**
     * Unblock a previously blocked astrologer.
     */
    public function unblockAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $astrologer = Astrologer::with('user')->find($astrologerId)
            ?? Astrologer::with('user')->where('user_id', $astrologerId)->first();

        if (!$astrologer || !$astrologer->user) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer not found.'], 404);
        }

        /** @var BlockService $blockService */
        $blockService = app(BlockService::class);
        $blockService->unblock($user, $astrologer->user);

        return response()->json([
            'status'  => 'success',
            'message' => 'Astrologer unblocked successfully.',
            'data'    => [
                'astrologer_id'      => $astrologer->id,
                'astrologer_user_id' => $astrologer->user->id,
                'is_blocked'         => false,
            ],
        ], 200);
    }

    /**
     * Get list of astrologers blocked by the authenticated user.
     */
    public function getBlockedAstrologers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => 'error', 'message' => 'Authenticated user not found or not a regular user.'], 404);
        }

        $perPage = (int) $request->input('per_page', 15);
        /** @var BlockService $blockService */
        $blockService = app(BlockService::class);
        $paginated = $blockService->getBlockedAstrologersForUser($user, $perPage);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'current_page' => $paginated->currentPage(),
                'data'         => $paginated->items(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ], 200);
    }
}