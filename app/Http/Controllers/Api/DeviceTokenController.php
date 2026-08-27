<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveDeviceTokenRequest;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class DeviceTokenController extends Controller
{
    /**
     * Register or refresh an FCM device token for the authenticated user.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();
            $fcmToken = $validated['fcm_token'];
            $deviceId = $validated['device_id'] ?? null;
            $deviceType = $validated['device_type'] ?? 'android';
            $deviceModel = $validated['device_model'] ?? null;
            $appVersion = $validated['app_version'] ?? null;

            // If a device_id is provided, delete any previous user associations on this physical device
            if ($deviceId) {
                UserDevice::where('device_id', $deviceId)
                    ->where('user_id', '!=', $user->id)
                    ->delete();
            }

            // Also delete any entries with identical token for another user
            UserDevice::where('fcm_token', $fcmToken)
                ->where('user_id', '!=', $user->id)
                ->delete();

            // Delete all previous devices for THIS user so only the current active device exists
            UserDevice::where('user_id', $user->id)
                ->where('fcm_token', '!=', $fcmToken)
                ->delete();

            // Find or create device record for current user
            $device = null;
            if ($deviceId) {
                $device = UserDevice::where('user_id', $user->id)
                    ->where('device_id', $deviceId)
                    ->first();
            }

            if (!$device) {
                $device = UserDevice::where('user_id', $user->id)
                    ->where('fcm_token', $fcmToken)
                    ->first();
            }

            if ($device) {
                $device->update([
                    'fcm_token'    => $fcmToken,
                    'device_type'  => $deviceType,
                    'device_id'    => $deviceId ?? $device->device_id,
                    'device_model' => $deviceModel ?? $device->device_model,
                    'app_version'  => $appVersion ?? $device->app_version,
                    'is_active'    => true,
                    'last_used_at' => now(),
                ]);
            } else {
                $device = UserDevice::create([
                    'user_id'      => $user->id,
                    'fcm_token'    => $fcmToken,
                    'device_type'  => $deviceType,
                    'device_id'    => $deviceId,
                    'device_model' => $deviceModel,
                    'app_version'  => $appVersion,
                    'is_active'    => true,
                    'last_used_at' => now(),
                ]);
            }

            // Sync with users table fcm_token column for backward compatibility
            $user->fcm_token = $fcmToken;
            $user->save();

            return ApiResponse::success([
                'device_id'    => $device->device_id,
                'device_type'  => $device->device_type,
                'is_active'    => $device->is_active,
                'last_used_at' => $device->last_used_at?->toIso8601String(),
            ], 'Device token registered successfully');

        } catch (Exception $e) {
            Log::error('DeviceToken store error: ' . $e->getMessage());
            return ApiResponse::error('Failed to register device token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove or deactivate FCM device token on user logout.
     */
    public function remove(RemoveDeviceTokenRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $fcmToken = $request->input('fcm_token');
            $deviceId = $request->input('device_id');

            $query = UserDevice::where('user_id', $user->id);

            if ($deviceId) {
                $query->where('device_id', $deviceId);
            } elseif ($fcmToken) {
                $query->where('fcm_token', $fcmToken);
            }

            $affected = $query->delete();

            // Clear users table fcm_token if matching or if no specific token given
            if (!$fcmToken || $user->fcm_token === $fcmToken) {
                $user->fcm_token = null;
                $user->save();
            }

            return ApiResponse::success([
                'deactivated_count' => $affected,
            ], 'Device token removed successfully');

        } catch (Exception $e) {
            Log::error('DeviceToken remove error: ' . $e->getMessage());
            return ApiResponse::error('Failed to remove device token: ' . $e->getMessage(), 500);
        }
    }
}
