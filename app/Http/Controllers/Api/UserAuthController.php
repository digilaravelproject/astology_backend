<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfilePhotoRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\User;
use App\Models\MatrimonyProfile;
use App\Services\NotificationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class UserAuthController extends Controller
{
    /**
     * Send OTP to user (creates account if doesn't exist).
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

            // Check if user exists
            $user = User::where('phone', $phone)->where('user_type', 'user')->first();

            // If user doesn't exist, create one
            if (!$user) {
                $user = User::create([
                    'name' => $phone, // Default name as phone
                    'phone' => $phone,
                    'user_type' => 'user',
                    'password' => bcrypt($phone), // Default password using phone
                ]);
            }

            // Generate 4-digit OTP
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $otp = '1234';

            // Store OTP (in users table - adding otp_* fields)
            $user->otp = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->otp_verified_at = null;
            $user->save();

            DB::commit();

            // Notify user about generated OTP (for audit/confirmation record).
            NotificationHelper::send(
                $user->id,
                'OTP generated',
                "A new OTP code was generated for your login.",
                ['phone' => $phone]
            );

            // For development/testing (no external SMS), return OTP in response.
            return response()->json([
                'status' => 'success',
                'message' => 'OTP generated and saved.',
                'data' => [
                    'phone' => $phone,
                    'user_id' => $user->id,
                    'otp' => $otp,
                    'expires_at' => $user->otp_expires_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User sendOtp error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while sending OTP.',
            ], 500);
        }
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

        try {
            DB::beginTransaction();

            $phone = $request->input('phone');
            $otp = $request->input('otp');

            $user = User::where('phone', $phone)->where('user_type', 'user')->lockForUpdate()->first();

            if (!$user) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
            }

            if (!$user->otp || !$user->otp_expires_at || Carbon::now()->gt($user->otp_expires_at)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'OTP expired or not generated.'], 422);
            }

            if ($user->otp !== $otp) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 422);
            }

            // OTP verified
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_verified_at = Carbon::now();
            $user->save();

            // Issue Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'OTP verified',
                'You have successfully verified your OTP and are now logged in.',
                ['phone' => $phone]
            );
            
            // Check matrimony profile exists
            $user->isMatrimony = MatrimonyProfile::where('user_id', $user->id)->exists();

            return response()->json([
                'status' => 'success',
                'message' => 'OTP verified.',
                'token' => $token,
                'token_type' => 'Bearer',
                'data' => [
                    'user' => $user,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User verifyOtp error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to verify OTP.'], 500);
        }
    }

    /**
     * Resend OTP (regenerate).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        // Same as sendOtp logic
        return $this->sendOtp($request);
    }

    /**
     * Get user profile by user ID.
     */
    public function getProfile($userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            if ($user->user_type !== 'user') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This is not a regular user.',
                ], 404);
            }

            // ✅ Check if matrimony profile exists
            $user->isMatrimony = MatrimonyProfile::where('user_id', $userId)->exists();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
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

    /**
     * Update user profile after OTP verification.
     */
    public function updateProfile(UpdateUserProfileRequest $request, $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            if ($user->user_type !== 'user') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This is not a regular user account.',
                ], 403);
            }

            // Check if user has verified OTP
            if (!$user->otp_verified_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please verify your phone number with OTP before updating profile.',
                ], 403);
            }

            DB::beginTransaction();

            // Update user profile fields
            $user->update([
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'date_of_birth' => $request->input('date_of_birth'),
                'time_of_birth' => $request->input('time_of_birth'),
                'place_of_birth' => $request->input('place_of_birth'),
                'languages' => $request->input('languages'),
                'profile_completed' => true,
            ]);

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'Profile updated',
                'Your profile has been successfully updated.',
                []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $user,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update user profile error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating profile.',
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
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        $file = $request->file('profile_photo');

        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'No profile_photo file was uploaded. Make sure you send a multipart/form-data request.',
            ], 422);
        }

        $filename = time() . '_' . $user->id . '_profile_photo.' . $file->getClientOriginalExtension();
        $path = 'users/' . $user->id . '/profile_photo';

        // Delete existing file if present
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
            'status' => 'success',
            'message' => 'Profile photo updated successfully.',
            'data' => [
                'user' => $user,
            ],
        ], 200);
    }

    /**
     * Update in-app user profile (authenticated).
     *
     * This endpoint is used when a logged-in user edits their profile in the app.
     */
    public function updateInAppProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'gender' => $request->input('gender'),
                'date_of_birth' => $request->input('date_of_birth'),
                'time_of_birth' => $request->input('time_of_birth'),
                'place_of_birth' => $request->input('place_of_birth'),
                'languages' => $request->input('languages'),
                'profile_completed' => true,
            ]);

            DB::commit();

            NotificationHelper::send(
                $user->id,
                'Profile updated',
                'Your profile has been successfully updated.',
                []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $user,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update in-app profile error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the profile.',
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
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer not found.',
            ], 404);
        }

        $community = AstrologerCommunity::where('astrologer_id', $astrologer->id)
            ->where('user_id', $user->id)
            ->first();

        // If already following, unfollow by deleting the relationship record.
        if ($community && $community->is_liked) {
            $community->delete();

            NotificationHelper::send(
                $user->id,
                'Astrologer unfollowed',
                "You have unfollowed astrologer {$astrologer->user->name}.",
                ['astrologer_id' => $astrologer->id]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Astrologer unfollowed.',
                'data' => [
                    'astrologer_id' => $astrologer->id,
                    'is_following' => false,
                    'followed_at' => null,
                ],
            ], 200);
        }

        // Otherwise create (or update) the follow relationship.
        if (! $community) {
            $community = new AstrologerCommunity([
                'astrologer_id' => $astrologer->id,
                'user_id' => $user->id,
            ]);
        }

        $community->is_liked = true;
        $community->liked_at = Carbon::now();
        $community->save();

        // Notify user
        NotificationHelper::send(
            $user->id,
            'Astrologer followed',
            "You are now following astrologer {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id]
        );

        // Notify astrologer
        NotificationHelper::send(
            $astrologer->user->id,
            'New follower',
            "{$user->name} has started following you.",
            ['user_id' => $user->id]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Astrologer followed.',
            'data' => [
                'astrologer_id' => $astrologer->id,
                'is_following' => true,
                'followed_at' => $community->liked_at,
            ],
        ], 200);
    }

    /**
     * Block an astrologer (will stop follow and record blocked status).
     */
    public function blockAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer not found.',
            ], 404);
        }

        $community = AstrologerCommunity::firstOrNew([
            'astrologer_id' => $astrologer->id,
            'user_id' => $user->id,
        ]);

        $community->is_liked = false;
        $community->liked_at = null;
        $community->is_blocked = true;
        $community->blocked_at = Carbon::now();
        $community->save();

        NotificationHelper::send(
            $user->id,
            'Astrologer blocked',
            "You have blocked astrologer {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id]
        );

        NotificationHelper::send(
            $astrologer->user->id,
            'You were blocked',
            "User {$user->name} has blocked you.",
            ['user_id' => $user->id]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Astrologer blocked.',
            'data' => [
                'astrologer_id' => $astrologer->id,
                'is_blocked' => true,
                'blocked_at' => $community->blocked_at,
            ],
        ], 200);
    }

    /**
     * Report an astrologer with a reason.
     */
    public function reportAstrologer(Request $request, $astrologerId): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Astrologer not found.',
            ], 404);
        }

        $community = AstrologerCommunity::firstOrNew([
            'astrologer_id' => $astrologer->id,
            'user_id' => $user->id,
        ]);

        $community->report_reason = $request->input('reason');
        $community->reported_at = Carbon::now();
        $community->save();

        NotificationHelper::send(
            $user->id,
            'Astrologer reported',
            "You have reported astrologer {$astrologer->user->name}.",
            ['astrologer_id' => $astrologer->id, 'reason' => $community->report_reason]
        );

        NotificationHelper::send(
            $astrologer->user->id,
            'You were reported',
            "Your account has been reported by user {$user->name}.",
            ['user_id' => $user->id, 'reason' => $community->report_reason]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Astrologer reported successfully.',
            'data' => [
                'astrologer_id' => $astrologer->id,
                'report_reason' => $community->report_reason,
                'reported_at' => $community->reported_at,
            ],
        ], 200);
    }

    /**
     * Get list of astrologers that the user is following.
     */
    public function getFollowing(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        try {
            // Get all astrologers the user is following
            $following = AstrologerCommunity::with(['astrologer.user'])
                ->where('user_id', $user->id)
                ->where('is_liked', true)
                ->orderByDesc('liked_at')
                ->get();

            $data = $following->map(function ($record) {
                $astrologer = $record->astrologer;
                return [
                    // 'astrologer_id' => $astrologer->id,
                    'user_id' => $astrologer->user->id,
                    'name' => $astrologer->user->name,
                    'email' => $astrologer->user->email,
                    'phone' => $astrologer->user->phone,
                    'profile_photo' => $astrologer->profile_photo,
                    'years_of_experience' => $astrologer->years_of_experience,
                    'areas_of_expertise' => $astrologer->areas_of_expertise,
                    'languages' => $astrologer->languages,
                    'bio' => $astrologer->bio,
                    'status' => $astrologer->status,
                    'followed_at' => $record->liked_at,
                    'created_at' => $record->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Following list retrieved successfully.',
                'data' => [
                    'count' => $data->count(),
                    'following' => $data,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get following list error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while retrieving the following list.',
            ], 500);
        }
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            // Revoke all tokens for the user
            $user->tokens()->delete();

            NotificationHelper::send(
                $user->id,
                'Logged out',
                'You have been logged out successfully.',
                []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'logged_out_at' => now(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('User logout error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while logging out.',
            ], 500);
        }
    }

    /**
     * Delete user account and all associated records.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Authenticated user not found or not a regular user.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $userId = $user->id;
            $userName = $user->name;

            // Delete all related records before deleting user
            // The following is deleted based on foreign key constraints

            // Delete wallet transactions
            \App\Models\WalletTransaction::where('wallet_id', function ($query) use ($userId) {
                $query->select('id')->from('wallets')->where('user_id', $userId);
            })->delete();

            // Delete wallet
            \App\Models\Wallet::where('user_id', $userId)->delete();

            // Delete reviews by user
            \App\Models\AstrologerReview::where('user_id', $userId)->delete();

            // Delete astrologer community records (following/followers relation)
            AstrologerCommunity::where('user_id', $userId)->delete();

            // Delete matrimony profiles
            \App\Models\MatrimonyProfile::where('user_id', $userId)->delete();

            // Delete notifications for user
            \App\Models\AppNotification::where('user_id', $userId)->delete();

            // Revoke all tokens
            $user->tokens()->delete();

            // Delete the user account
            $user->delete();

            DB::commit();

            Log::info("User account deleted: ID={$userId}, Name={$userName}");

            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully. All your data has been removed.',
                'data' => [
                    'user_id' => $userId,
                    'deleted_at' => now(),
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete user account error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the account.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }
}

