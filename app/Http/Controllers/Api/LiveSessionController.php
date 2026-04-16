<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveSession;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

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
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s|after:now',
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

            // Create live session
            $liveSession = LiveSession::create([
                'astrologer_id' => $astrologer->id,
                'title' => $request->title,
                'description' => $request->description,
                'scheduled_at' => $request->scheduled_at,
                'session_type' => $request->session_type,
                'status' => 'upcoming',
                'duration_minutes' => $request->duration_minutes ?? 60,
                'max_participants' => $request->max_participants ?? 100,
            ]);

            return ApiResponse::success(
                'Live session created successfully',
                $this->formatLiveSession($liveSession),
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
                    'Live sessions retrieved successfully',
                    [
                        'data' => $sessions->map(fn($session) => $this->formatLiveSession($session))->values(),
                        'pagination' => [
                            'current_page' => $sessions->currentPage(),
                            'total_pages' => $sessions->lastPage(),
                            'per_page' => $sessions->perPage(),
                            'total' => $sessions->total(),
                        ]
                    ]
                );
            } elseif ($filter === 'completed') {
                $query = $query->completed();
                $sessions = $query->paginate($perPage);
                
                return ApiResponse::success(
                    'Live sessions retrieved successfully',
                    [
                        'data' => $sessions->map(fn($session) => $this->formatLiveSession($session))->values(),
                        'pagination' => [
                            'current_page' => $sessions->currentPage(),
                            'total_pages' => $sessions->lastPage(),
                            'per_page' => $sessions->perPage(),
                            'total' => $sessions->total(),
                        ]
                    ]
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

                return ApiResponse::success('Live sessions retrieved successfully', [
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
                ]);
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

            return ApiResponse::success('Live session retrieved successfully', $this->formatLiveSession($liveSession));
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

            return ApiResponse::success('Live session updated successfully', $this->formatLiveSession($liveSession));
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

            return ApiResponse::success("Live session '{$title}' deleted successfully", null, 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete live session: ' . $e->getMessage(), 500);
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
            'duration_minutes' => $session->duration_minutes,
            'max_participants' => $session->max_participants,
            'current_participants' => $session->current_participants,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
