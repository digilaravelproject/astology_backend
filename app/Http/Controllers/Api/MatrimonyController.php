<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatrimonyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MatrimonyController extends Controller
{
    /**
     * Create or update a matrimony profile for authenticated user.
     */
    public function createProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'created_for' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'height' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'annual_income' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'file', 'image', 'max:5120'],
            'pan_card_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'driving_licence_number' => ['nullable', 'string', 'max:50'],
            'aadhar_card_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{12}$/'],
        ]);

        try {
            return DB::transaction(function () use ($user, $validated, $request) {

                $profile = MatrimonyProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    array_merge($validated, ['user_id' => $user->id])
                );

                if ($request->hasFile('profile_photo')) {
                    $file = $request->file('profile_photo');

                    $filename = time() . '_' . $user->id . '_matrimony.' .
                        $file->getClientOriginalExtension();

                    $path = 'matrimony_profiles/' . $user->id;

                    if ($profile->profile_photo &&
                        Storage::disk('public')->exists($profile->profile_photo)) {

                        Storage::disk('public')->delete($profile->profile_photo);
                    }

                    $profile->profile_photo =
                        Storage::disk('public')->putFileAs(
                            $path,
                            $file,
                            $filename
                        );

                    $profile->save();
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Matrimony profile saved successfully.',
                    'data' => [
                        'profile' => $profile,
                    ],
                ], 201);
            });

        } catch (\Exception $e) {

            Log::error(
                'Matrimony profile save error: ' .
                $e->getMessage()
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save profile.'
            ], 500);
        }
    }

    /**
     * List other matrimony profiles
     */
    public function listProfiles(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $query = MatrimonyProfile::with('user')
            ->where('user_id', '!=', $user->id);

        if ($location = $request->query('location')) {
            $query->where(
                'location',
                'like',
                '%' . $location . '%'
            );
        }

        if ($education = $request->query('education')) {
            $query->where(
                'education',
                'like',
                '%' . $education . '%'
            );
        }

        if ($gender = $request->query('gender')) {
            $query->where('gender', $gender);
        }

        $profiles = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'profiles' => $profiles,
            ],
        ], 200);
    }

    /**
     * Show single profile
     */
    public function showProfile(
        Request $request,
        $id
    ): JsonResponse {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $profile = MatrimonyProfile::with('user')
            ->find($id);

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $profile,
            ],
        ], 200);
    }

    /**
     * Search profiles
     */
    public function searchProfiles(
        Request $request
    ): JsonResponse {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $queryText = $request->query('q');

        $query = MatrimonyProfile::with('user')
            ->where('user_id', '!=', $user->id);

        if ($queryText) {

            $query->where(function ($q) use ($queryText) {

                $q->where(
                    'first_name',
                    'like',
                    "%{$queryText}%"
                )
                ->orWhere(
                    'last_name',
                    'like',
                    "%{$queryText}%"
                )
                ->orWhere(
                    'location',
                    'like',
                    "%{$queryText}%"
                )
                ->orWhere(
                    'education',
                    'like',
                    "%{$queryText}%"
                )
                ->orWhere(
                    'job_title',
                    'like',
                    "%{$queryText}%"
                );

            });
        }

        $profiles = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'profiles' => $profiles,
            ],
        ], 200);
    }
}