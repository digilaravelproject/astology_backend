<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AstrologerDefaultMessage;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AstrologerDefaultMessageController extends Controller
{
    /**
     * Get all default message templates for the logged-in astrologer.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->user_type !== 'astrologer') {
                return ApiResponse::error('Unauthorized access.', 403);
            }

            $messages = AstrologerDefaultMessage::where('astrologer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return ApiResponse::success($messages, 'Default messages retrieved successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve messages.', 500);
        }
    }

    /**
     * Get the active default message for the logged-in astrologer.
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->user_type !== 'astrologer') {
                return ApiResponse::error('Unauthorized access.', 403);
            }

            $message = AstrologerDefaultMessage::where('astrologer_id', $user->id)
                ->where('is_default', true)
                ->first();

            if (!$message) {
                return ApiResponse::success(null, 'No active default message found.');
            }

            return ApiResponse::success($message, 'Active default message retrieved successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve active message.', 500);
        }
    }

    /**
     * Create a new default message template.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->user_type !== 'astrologer') {
            return ApiResponse::error('Unauthorized access.', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000|min:1',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed.', 422, $validator->errors());
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                $isDefault = $request->boolean('is_default', false);

                if ($isDefault) {
                    // Set all others to false first
                    AstrologerDefaultMessage::where('astrologer_id', $user->id)
                        ->update(['is_default' => false]);
                }

                $message = AstrologerDefaultMessage::create([
                    'astrologer_id' => $user->id,
                    'title' => $request->filled('title') ? strip_tags($request->input('title')) : null,
                    'content' => strip_tags($request->input('content')),
                    'is_default' => $isDefault,
                ]);

                return ApiResponse::success($message, 'Default message created successfully.', 201);
            });
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create message template.', 500);
        }
    }

    /**
     * Update an existing default message template.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->user_type !== 'astrologer') {
            return ApiResponse::error('Unauthorized access.', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000|min:1',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed.', 422, $validator->errors());
        }

        try {
            return DB::transaction(function () use ($request, $user, $id) {
                $message = AstrologerDefaultMessage::where('id', $id)
                    ->where('astrologer_id', $user->id)
                    ->first();

                if (!$message) {
                    return ApiResponse::error('Message template not found or unauthorized.', 404);
                }

                $isDefault = $request->boolean('is_default', false);
                if ($isDefault && !$message->is_default) {
                    // Make sure other messages are set to false
                    AstrologerDefaultMessage::where('astrologer_id', $user->id)
                        ->update(['is_default' => false]);
                }

                $message->update([
                    'title' => $request->filled('title') ? strip_tags($request->input('title')) : null,
                    'content' => strip_tags($request->input('content')),
                    'is_default' => $isDefault,
                ]);

                return ApiResponse::success($message, 'Default message updated successfully.');
            });
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update message template.', 500);
        }
    }

    /**
     * Delete a default message template.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->user_type !== 'astrologer') {
                return ApiResponse::error('Unauthorized access.', 403);
            }

            $message = AstrologerDefaultMessage::where('id', $id)
                ->where('astrologer_id', $user->id)
                ->first();

            if (!$message) {
                return ApiResponse::error('Message template not found or unauthorized.', 404);
            }

            $message->delete();

            return ApiResponse::success(null, 'Default message deleted successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete message template.', 500);
        }
    }

    /**
     * Mark a specific template as the active default message.
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->user_type !== 'astrologer') {
                return ApiResponse::error('Unauthorized access.', 403);
            }

            return DB::transaction(function () use ($user, $id) {
                $message = AstrologerDefaultMessage::where('id', $id)
                    ->where('astrologer_id', $user->id)
                    ->first();

                if (!$message) {
                    return ApiResponse::error('Message template not found or unauthorized.', 404);
                }

                // Set all other messages of this astrologer to non-default
                AstrologerDefaultMessage::where('astrologer_id', $user->id)
                    ->update(['is_default' => false]);

                // Set this one as default
                $message->is_default = true;
                $message->save();

                return ApiResponse::success($message, 'Message template set as active default.');
            });
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to set default message.', 500);
        }
    }
}
