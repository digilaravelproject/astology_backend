<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class LiveSessionController extends Controller
{
    public function __construct(
        protected LiveSessionService $liveSessionService,
    ) {}

    private function getAstrologer()
    {
        $astrologer = auth()->user()->astrologer;
        if (!$astrologer) {
            return null;
        }
        return $astrologer;
    }

    private function mapErrorCode(Exception $e, int $default = 500): int
    {
        return match (true) {
            str_contains($e->getMessage(), 'not found') => 404,
            str_contains($e->getMessage(), 'not an astrologer') => 403,
            str_contains($e->getMessage(), 'Cannot') => 422,
            str_contains($e->getMessage(), 'Only') => 422,
            str_contains($e->getMessage(), 'No active') => 422,
            default => $default,
        };
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_instant' => 'nullable|boolean',
            'scheduled_at' => 'required_unless:is_instant,true|nullable|date_format:Y-m-d H:i:s|after:now',
            'session_type' => 'required|in:public,private',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'max_participants' => 'nullable|integer|min:1|max:5000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->createSession(
                $astrologer->id,
                $request->all()
            );

            return ApiResponse::success($result, 'Live session created successfully', 201);
        } catch (Exception $e) {
            Log::error('Failed to create live session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to create live session: ' . $e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $filter = $request->get('filter', 'all');
            $perPage = (int) $request->get('per_page', 15);

            $result = $this->liveSessionService->getAstrologerSessions(
                $astrologer->id,
                $filter,
                $perPage
            );

            return ApiResponse::success($result, 'Live sessions retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error('Failed to retrieve live sessions: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->getAstrologerSession(
                $astrologer->id,
                (int) $id
            );

            return ApiResponse::success($result, 'Live session retrieved successfully');
        } catch (Exception $e) {
            $code = $this->mapErrorCode($e);
            if ($code >= 500) {
                return ApiResponse::error('Failed to retrieve live session: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'sometimes|required|date_format:Y-m-d H:i:s|after:now',
            'session_type' => 'sometimes|required|in:public,private',
            'status' => 'sometimes|required|in:upcoming,ongoing,completed,cancelled',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'max_participants' => 'nullable|integer|min:1|max:5000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->updateSession(
                $astrologer->id,
                (int) $id,
                $request->all()
            );

            return ApiResponse::success($result, 'Live session updated successfully');
        } catch (Exception $e) {
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to update live session: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function destroy($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $title = $this->liveSessionService->deleteSession(
                $astrologer->id,
                (int) $id
            );

            return ApiResponse::success(null, "Live session '{$title}' deleted successfully", 200);
        } catch (Exception $e) {
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to delete live session: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function start($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->startSession(
                $astrologer->id,
                (int) $id
            );

            return ApiResponse::success($result, 'Live session started successfully');
        } catch (Exception $e) {
            Log::error('Failed to start live session', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to start live session: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function stop($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->stopSession(
                $astrologer->id,
                (int) $id
            );

            return ApiResponse::success($result, 'Live session ended successfully');
        } catch (Exception $e) {
            Log::error('Failed to stop live session', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to stop live session: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function current()
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->getCurrentSession(
                $astrologer->id
            );

            if ($result === null) {
                return ApiResponse::success(null, 'No active live session found');
            }

            return ApiResponse::success($result, 'Current active live session retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error('Failed to retrieve current live session: ' . $e->getMessage(), 500);
        }
    }

    public function broadcast($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->startBroadcast(
                $astrologer->id,
                (int) $id
            );

            $message = $result['already_active'] ? 'Broadcast already active' : 'Broadcast started successfully';
            return ApiResponse::success($result['data'], $message);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 503);
        } catch (Exception $e) {
            Log::error('Failed to start broadcast', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to start broadcast: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function stopBroadcast($id)
    {
        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $this->liveSessionService->stopBroadcast(
                $astrologer->id,
                (int) $id
            );

            return ApiResponse::success(null, 'Broadcast stopped successfully');
        } catch (Exception $e) {
            Log::error('Failed to stop broadcast', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to stop broadcast: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function updateMediaStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:camera,audio',
            'status' => 'required|string|in:on,off',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $astrologer = $this->getAstrologer();
        if (!$astrologer) {
            return ApiResponse::error('User is not an astrologer', 403);
        }

        try {
            $result = $this->liveSessionService->updateMediaStatus(
                $astrologer->id,
                (int) $id,
                $request->type,
                $request->status
            );

            return ApiResponse::success($result, 'Media status updated');
        } catch (Exception $e) {
            $code = $this->mapErrorCode($e, 500);
            if ($code >= 500) {
                return ApiResponse::error('Failed to update media status: ' . $e->getMessage(), $code);
            }
            return ApiResponse::error($e->getMessage(), $code);
        }
    }
}
