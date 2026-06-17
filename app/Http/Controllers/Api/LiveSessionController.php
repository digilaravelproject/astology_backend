<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Helpers\ApiResponse;
use App\Services\LiveKitService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LiveSessionController extends Controller
{
    /**
     * Create a new live session
     * POST /api/v1/astrologer/live
     */
    public function store(Request $request)
    {
        // Validate input
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

        try {
            // Get authenticated astrologer
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $isInstant = $request->boolean('is_instant', false);

            // Create live session
            $liveSessionData = [
                'astrologer_id' => $astrologer->id,
                'title' => $request->title,
                'description' => $request->description,
                'scheduled_at' => $isInstant ? now() : $request->scheduled_at,
                'session_type' => $request->session_type,
                'status' => $isInstant ? 'ongoing' : 'upcoming',
                'duration_minutes' => $request->duration_minutes ?? 60,
                'max_participants' => $request->max_participants ?? 100,
            ];

            $liveSession = LiveSession::create($liveSessionData);

            if ($isInstant) {
                $freshSession = $liveSession->fresh(['astrologer.user']);
                try {
                    broadcast(new \App\Events\LiveSessionStarted($freshSession));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast LiveSessionStarted on create', ['error' => $e->getMessage()]);
                }
                try {
                    $this->notifyAllUsersAboutLive($freshSession);
                } catch (\Exception $e) {
                    Log::error('Failed to notify users about live', ['error' => $e->getMessage()]);
                }
            }

            return ApiResponse::success(
                $this->formatLiveSession($liveSession),
                'Live session created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create live session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to create live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get upcoming and completed live sessions
     * GET /api/v1/astrologer/live?filter=upcoming|completed|all
     */
    public function index(Request $request)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $filter = $request->get('filter', 'all'); // upcoming, completed, all
            $perPage = $request->get('per_page', 15);

            $query = LiveSession::where('astrologer_id', $astrologer->id);

            // Apply filter
            if ($filter === 'upcoming') {
                $query = $query->upcoming();
                $sessions = $query->paginate($perPage);
                
                return ApiResponse::success(
                    [
                        'data' => $sessions->map(fn($session) => $this->formatLiveSession($session))->values(),
                        'pagination' => [
                            'current_page' => $sessions->currentPage(),
                            'total_pages' => $sessions->lastPage(),
                            'per_page' => $sessions->perPage(),
                            'total' => $sessions->total(),
                        ]
                    ],
                    'Live sessions retrieved successfully'
                );
            } elseif ($filter === 'completed') {
                $query = $query->completed();
                $sessions = $query->paginate($perPage);
                
                return ApiResponse::success(
                    [
                        'data' => $sessions->map(fn($session) => $this->formatLiveSession($session))->values(),
                        'pagination' => [
                            'current_page' => $sessions->currentPage(),
                            'total_pages' => $sessions->lastPage(),
                            'per_page' => $sessions->perPage(),
                            'total' => $sessions->total(),
                        ]
                    ],
                    'Live sessions retrieved successfully'
                );
            } else {
                // Return all, separated by status
                $upcoming = LiveSession::where('astrologer_id', $astrologer->id)
                    ->upcoming()
                    ->get()
                    ->map(fn($session) => $this->formatLiveSession($session));

                $completedQuery = LiveSession::where('astrologer_id', $astrologer->id)
                    ->completed()
                    ->paginate($perPage);

                return ApiResponse::success([
                    'upcoming' => [
                        'data' => $upcoming->values(),
                        'total' => $upcoming->count(),
                    ],
                    'completed' => [
                        'data' => $completedQuery->map(fn($session) => $this->formatLiveSession($session))->values(),
                        'pagination' => [
                            'current_page' => $completedQuery->currentPage(),
                            'total_pages' => $completedQuery->lastPage(),
                            'per_page' => $completedQuery->perPage(),
                            'total' => $completedQuery->total(),
                        ]
                    ]
                ], 'Live sessions retrieved successfully');
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve live sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific live session
     * GET /api/v1/astrologer/live/{id}
     */
    public function show($id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            return ApiResponse::success($this->formatLiveSession($liveSession), 'Live session retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a live session
     * PUT /api/v1/astrologer/live/{id}
     */
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

        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            // Don't allow updating past sessions
            if ($liveSession->scheduled_at < now() && $request->has('scheduled_at')) {
                return ApiResponse::error('Cannot update scheduled time for past sessions', 422);
            }

            $liveSession->update($request->only([
                'title',
                'description',
                'scheduled_at',
                'session_type',
                'status',
                'duration_minutes',
                'max_participants',
            ]));

            return ApiResponse::success($this->formatLiveSession($liveSession), 'Live session updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a live session
     * DELETE /api/v1/astrologer/live/{id}
     */
    public function destroy($id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            // Don't allow deleting ongoing sessions
            if ($liveSession->status === 'ongoing') {
                return ApiResponse::error('Cannot delete an ongoing live session', 422);
            }

            $title = $liveSession->title;
            $liveSession->delete();

            return ApiResponse::success(null, "Live session '{$title}' deleted successfully", 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Start a live session (go live)
     * POST /api/v1/astrologer/live/{id}/start
     */
    public function start($id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            if ($liveSession->status !== 'upcoming') {
                return ApiResponse::error('Only upcoming sessions can be started', 422);
            }

            $liveSession->update([
                'status'     => 'ongoing',
            ]);

            $freshSession = $liveSession->fresh(['astrologer.user']);
            try {
                broadcast(new \App\Events\LiveSessionStarted($freshSession));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast LiveSessionStarted on start', ['error' => $e->getMessage()]);
            }
            try {
                $this->notifyAllUsersAboutLive($freshSession);
            } catch (\Exception $e) {
                Log::error('Failed to notify users about live', ['error' => $e->getMessage()]);
            }

            return ApiResponse::success(
                $this->formatLiveSession($freshSession),
                'Live session started successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to start live session', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to start live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stop a live session (end stream)
     * POST /api/v1/astrologer/live/{id}/stop
     */
    public function stop($id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            if ($liveSession->status !== 'ongoing') {
                return ApiResponse::error('Only ongoing sessions can be stopped', 422);
            }

            // Delete LiveKit room BEFORE clearing room_uuid
            $existingRoomUuid = $liveSession->room_uuid;
            if ($existingRoomUuid) {
                try {
                    app(LiveKitService::class)->deleteRoom($existingRoomUuid);
                } catch (\Exception $e) {
                    Log::error('Failed to delete LiveKit room during stop', [
                        'room' => $existingRoomUuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                broadcast(new \App\Events\AstrologerMediaStatusChanged(
                    $liveSession->id,
                    ['live_session_id' => $liveSession->id, 'user_id' => auth()->id(), 'type' => 'camera', 'status' => 'off']
                ));
                broadcast(new \App\Events\AstrologerMediaStatusChanged(
                    $liveSession->id,
                    ['live_session_id' => $liveSession->id, 'user_id' => auth()->id(), 'type' => 'audio', 'status' => 'off']
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast media status on session end', ['error' => $e->getMessage()]);
            }

            $liveSession->update([
                'status' => 'completed',
                'is_broadcasting' => false,
                'is_camera_on' => false,
                'is_audio_on' => false,
                'room_uuid' => null,
            ]);

            $freshSession = $liveSession->fresh();

            try {
                broadcast(new \App\Events\LiveSessionEnded($freshSession));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast LiveSessionEnded', ['error' => $e->getMessage()]);
            }

            return ApiResponse::success(
                $this->formatLiveSession($freshSession),
                'Live session ended successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to stop live session', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to stop live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the currently ongoing live session for the astrologer
     * GET /api/v1/astrologer/live/current
     */
    public function current()
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('status', 'ongoing')
                ->first();

            if (!$liveSession) {
                return ApiResponse::success(null, 'No active live session found');
            }

            return ApiResponse::success(
                $this->formatLiveSession($liveSession),
                'Current active live session retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve current live session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Start LiveKit broadcast for an ongoing session
     * POST /api/v1/astrologer/live/{id}/broadcast
     */
    public function broadcast(LiveKitService $liveKit, $id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            if ($liveSession->status !== 'ongoing') {
                return ApiResponse::error('Only ongoing sessions can start broadcasting', 422);
            }

            if ($liveSession->room_uuid && $liveSession->is_broadcasting) {
                $token = $liveKit->generateToken(
                    $liveSession->room_uuid,
                    'astro_' . $astrologer->user_id,
                    canPublish: true
                );

                return ApiResponse::success([
                    'livekit_ws_url' => $liveKit->getWsUrl(),
                    'room_uuid' => $liveSession->room_uuid,
                    'token' => $token,
                ], 'Broadcast already active');
            }

            $roomName = 'live_' . $liveSession->id;

            try {
                $liveKit->createRoom($roomName);
            } catch (\RuntimeException $e) {
                return ApiResponse::error($e->getMessage(), 503);
            }

            $liveSession->update([
                'room_uuid' => $roomName,
                'is_broadcasting' => true,
            ]);

            LiveSessionParticipant::updateOrCreate(
                [
                    'live_session_id' => $liveSession->id,
                    'user_id' => auth()->id(),
                    'role' => 'astrologer',
                ],
                [
                    'livekit_identity' => 'astro_' . auth()->id(),
                    'joined_at' => now(),
                    'left_at' => null,
                ]
            );

            $token = $liveKit->generateToken(
                $roomName,
                'astro_' . auth()->id(),
                canPublish: true
            );

            try {
                broadcast(new \App\Events\AstrologerBroadcastStarted($liveSession->fresh()));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast AstrologerBroadcastStarted', ['error' => $e->getMessage()]);
            }

            return ApiResponse::success([
                'livekit_ws_url' => $liveKit->getWsUrl(),
                'room_uuid' => $roomName,
                'token' => $token,
            ], 'Broadcast started successfully');
        } catch (\Exception $e) {
            Log::error('Failed to start broadcast', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to start broadcast: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stop LiveKit broadcast without ending the session
     * POST /api/v1/astrologer/live/{id}/stop-broadcast
     */
    public function stopBroadcast(LiveKitService $liveKit, $id)
    {
        try {
            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            if (!$liveSession->is_broadcasting || !$liveSession->room_uuid) {
                return ApiResponse::error('No active broadcast to stop', 422);
            }

            try {
                $liveKit->deleteRoom($liveSession->room_uuid);
            } catch (\Exception $e) {
                Log::error('Failed to delete LiveKit room', ['room' => $liveSession->room_uuid, 'error' => $e->getMessage()]);
            }

            LiveSessionParticipant::where('live_session_id', $liveSession->id)
                ->whereNull('left_at')
                ->update(['left_at' => now()]);

            $liveSession->update([
                'is_broadcasting' => false,
                'is_camera_on' => false,
                'is_audio_on' => false,
                'room_uuid' => null,
            ]);

            try {
                broadcast(new \App\Events\AstrologerMediaStatusChanged(
                    $liveSession->id,
                    ['live_session_id' => $liveSession->id, 'user_id' => auth()->id(), 'type' => 'camera', 'status' => 'off']
                ));
                broadcast(new \App\Events\AstrologerMediaStatusChanged(
                    $liveSession->id,
                    ['live_session_id' => $liveSession->id, 'user_id' => auth()->id(), 'type' => 'audio', 'status' => 'off']
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast media status on broadcast stop', ['error' => $e->getMessage()]);
            }

            return ApiResponse::success(null, 'Broadcast stopped successfully');
        } catch (\Exception $e) {
            Log::error('Failed to stop broadcast', [
                'live_session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Failed to stop broadcast: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update astrologer media status (camera/audio on/off)
     * POST /api/v1/astrologer/live/{id}/media-status
     */
    public function updateMediaStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:camera,audio',
                'status' => 'required|string|in:on,off',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', 422, $validator->errors());
            }

            $astrologer = auth()->user()->astrologer;

            if (!$astrologer) {
                return ApiResponse::error('User is not an astrologer', 403);
            }

            $liveSession = LiveSession::where('astrologer_id', $astrologer->id)
                ->where('id', $id)
                ->first();

            if (!$liveSession) {
                return ApiResponse::error('Live session not found', 404);
            }

            if (!$liveSession->is_broadcasting) {
                return ApiResponse::error('No active broadcast', 422);
            }

            $status = $request->status === 'on';

            if ($request->type === 'camera') {
                $liveSession->update(['is_camera_on' => $status]);
            } elseif ($request->type === 'audio') {
                $liveSession->update(['is_audio_on' => $status]);
            }

            try {
                broadcast(new \App\Events\AstrologerMediaStatusChanged(
                    $liveSession->id,
                    [
                        'live_session_id' => $liveSession->id,
                        'user_id' => auth()->id(),
                        'type' => $request->type,
                        'status' => $request->status,
                    ]
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast media status', ['error' => $e->getMessage()]);
            }

            return ApiResponse::success([
                'live_session_id' => $liveSession->id,
                'is_camera_on' => $liveSession->fresh()->is_camera_on,
                'is_audio_on' => $liveSession->fresh()->is_audio_on,
            ], 'Media status updated');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update media status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send notification to all users when astrologer goes live
     */
    private function notifyAllUsersAboutLive($liveSession)
    {
        try {
            $astrologerUser = $liveSession->astrologer?->user;
            if ($astrologerUser) {
                $title = "Astrologer Live Now!";
                $body = "{$astrologerUser->name} is now streaming live. Join the session to ask your questions!";
                $meta = [
                    'type' => 'live_session',
                    'live_session_id' => $liveSession->id,
                ];

                \App\Models\User::chunk(100, function ($users) use ($title, $body, $meta) {
                    foreach ($users as $user) {
                        \App\Services\NotificationHelper::send($user->id, $title, $body, $meta);
                    }
                });
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send live notifications: ' . $e->getMessage());
        }
    }

    /**
     * Format live session response
     */
    private function formatLiveSession($session)
    {
        return [
            'id' => $session->id,
            'astrologer_id' => $session->astrologer_id,
            'title' => $session->title,
            'description' => $session->description,
            'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i:s'),
            'scheduled_date' => $session->scheduled_at->format('Y-m-d'),
            'scheduled_time' => $session->scheduled_at->format('H:i:s'),
            'session_type' => $session->session_type,
            'status' => $session->status,
            'is_broadcasting' => $session->is_broadcasting,
            'duration_minutes' => $session->duration_minutes,
            'max_participants' => $session->max_participants,
            'current_participants' => $session->current_participants,
            'viewer_count' => $session->viewer_count,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
