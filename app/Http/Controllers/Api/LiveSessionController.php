<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveSession;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

            if ($isInstant) {
                $liveSessionData['started_at'] = now();
                $liveSessionData['stream_key'] = Str::random(32);
            }

            $liveSession = LiveSession::create($liveSessionData);

            if ($isInstant) {
                $freshSession = $liveSession->fresh(['astrologer.user']);
                broadcast(new \App\Events\LiveSessionStarted($freshSession));
                $this->notifyAllUsersAboutLive($freshSession);
            }

            return ApiResponse::success(
                $this->formatLiveSession($liveSession),
                'Live session created successfully',
                201
            );
        } catch (\Exception $e) {
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
                'started_at' => now(),
                'stream_key' => Str::random(32),
            ]);

            $freshSession = $liveSession->fresh(['astrologer.user']);
            broadcast(new \App\Events\LiveSessionStarted($freshSession));
            $this->notifyAllUsersAboutLive($freshSession);

            return ApiResponse::success(
                $this->formatLiveSession($freshSession),
                'Live session started successfully'
            );
        } catch (\Exception $e) {
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

            $durationMinutes = $liveSession->started_at
                ? (int) $liveSession->started_at->diffInMinutes(now())
                : 0;

            $liveSession->update([
                'status'           => 'completed',
                'ended_at'         => now(),
                'duration_minutes' => $durationMinutes,
            ]);

            return ApiResponse::success(
                $this->formatLiveSession($liveSession->fresh()),
                'Live session ended successfully'
            );
        } catch (\Exception $e) {
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
            'live_url' => $session->live_url,
            'stream_key' => $session->stream_key,
            'stream_url' => $session->stream_url,
            'started_at' => $session->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $session->ended_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $session->duration_minutes,
            'max_participants' => $session->max_participants,
            'current_participants' => $session->current_participants,
            'viewer_count' => $session->viewer_count,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
