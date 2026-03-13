<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfilePhotoRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            \Log::error('User sendOtp error: ' . $e->getMessage());

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

        $phone = $request->input('phone');
        $otp = $request->input('otp');

        $user = User::where('phone', $phone)->where('user_type', 'user')->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        if (!$user->otp || !$user->otp_expires_at || Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired or not generated.'], 422);
        }

        if ($user->otp !== $otp) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 422);
        }

        // OTP verified
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->otp_verified_at = Carbon::now();
        $user->save();

        // Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified.',
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => [
                'user' => $user,
            ],
        ], 200);
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

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
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

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $user,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update user profile error: ' . $e->getMessage());

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

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $user,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update in-app profile error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the profile.',
            ], 500);
        }
    }
}
