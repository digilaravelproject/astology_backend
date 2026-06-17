<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\LiveSession;
use App\Services\LiveSessionService;
use App\Services\SuperChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SuperChatController extends Controller
{
    public function __construct(
        protected LiveSessionService $liveSessionService,
        protected SuperChatService $superChatService,
    ) {}

    public function nowStreaming()
    {
        try {
            return ApiResponse::success(
                $this->liveSessionService->getActiveSessions(),
                'Live sessions retrieved successfully'
            );
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            return ApiResponse::success(
                $this->liveSessionService->getSessionDetail($id),
                'Live session retrieved successfully'
            );
        } catch (Exception $e) {
            return ApiResponse::error('Live session not found', 404);
        }
    }

    public function watch($id)
    {
        try {
            $data = $this->liveSessionService->generateWatchToken($id, auth()->user());
            return ApiResponse::success($data, 'Watch token generated successfully');
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function join($id)
    {
        try {
            $this->liveSessionService->joinSession($id, auth()->user());
            return ApiResponse::success(null, 'Joined live session successfully');
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function leave($id)
    {
        try {
            $this->liveSessionService->leaveSession($id, auth()->user());
            return ApiResponse::success(null, 'Left live session successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function comment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $this->liveSessionService->addComment($id, $request->user(), $request->message);
            return ApiResponse::success($data, 'Comment sent successfully', 201);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function sendSuperChat(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|integer|exists:gifts,id',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        try {
            $session = LiveSession::findOrFail($id);

            if ($session->status !== 'ongoing') {
                return ApiResponse::error('Live session is not currently active', 400);
            }

            $result = $this->superChatService->processSuperChat(
                $session, $request->user(), (int) $request->gift_id, $request->message
            );

            return ApiResponse::success([
                'id' => $result['superChat']->id,
                'amount' => $result['superChat']->amount,
                'message' => $result['superChat']->message,
                'created_at' => $result['superChat']->created_at->toISOString(),
            ], 'Super Chat sent successfully', 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function comments(Request $request, $id)
    {
        try {
            $perPage = min((int) $request->query('per_page', 50), 100);
            return ApiResponse::success(
                $this->liveSessionService->getComments($id, $perPage),
                'Comments retrieved successfully'
            );
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
