<?php

namespace App\Services;

use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveComment;
use App\Models\User;
use App\Events\NewLiveComment;
use App\Events\UserJoinedLiveSession;
use App\Events\UserLeftLiveSession;
use App\Events\ViewerCountUpdated;
use App\Events\LiveSessionStarted;
use App\Events\LiveSessionEnded;
use App\Events\AstrologerBroadcastStarted;
use App\Events\AstrologerMediaStatusChanged;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LiveSessionService
{
    public function __construct(
        protected LiveKitService $liveKit,
    ) {}

    public function getActiveSessions(): Collection
    {
        return LiveSession::with('astrologer.user:id,name,profile_photo')
            ->where('status', 'ongoing')
            ->where('session_type', 'public')
            ->latest('id')
            ->get()
            ->map(fn($session) => $this->formatSessionListItem($session));
    }

    public function getSessionDetail(int $id): array
    {
        $session = LiveSession::with([
            'astrologer.user:id,name,gender,profile_photo',
            'astrologer.skill',
        ])->findOrFail($id);

        return $this->formatSessionDetail($session);
    }

    public function generateWatchToken(int $id, $user): array
    {
        $session = LiveSession::findOrFail($id);

        if ($session->status !== 'ongoing') {
            throw new \RuntimeException('Live session is not currently active');
        }

        if (!$session->is_broadcasting || !$session->room_uuid) {
            throw new \RuntimeException('Broadcast has not started yet');
        }

        $identity = 'user_' . $user->id;

        LiveSessionParticipant::updateOrCreate(
            [
                'live_session_id' => $session->id,
                'user_id' => $user->id,
                'role' => 'viewer',
            ],
            [
                'livekit_identity' => $identity,
            ]
        );

        $token = $this->liveKit->generateToken(
            $session->room_uuid,
            $identity,
            canPublish: false,
            canSubscribe: true
        );

        return [
            'livekit_ws_url' => $this->liveKit->getWsUrl(),
            'room_uuid' => $session->room_uuid,
            'token' => $token,
        ];
    }

    public function joinSession(int $id, $user): array
    {
        $session = LiveSession::with([
            'astrologer.user:id,name,gender,profile_photo',
            'astrologer.skill',
        ])->findOrFail($id);

        if ($session->status !== 'ongoing') {
            throw new \RuntimeException('Live session is not currently active');
        }

        $participant = LiveSessionParticipant::where('live_session_id', $session->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        $shouldIncrement = false;

        if (!$participant) {
            $participant = LiveSessionParticipant::updateOrCreate(
                ['live_session_id' => $session->id, 'user_id' => $user->id, 'role' => 'viewer'],
                ['joined_at' => now(), 'left_at' => null]
            );
            $shouldIncrement = true;
        } elseif (!$participant->joined_at) {
            $participant->update(['joined_at' => now()]);
            $shouldIncrement = true;
        }

        if ($shouldIncrement) {
            $session->increment('viewer_count');
            $session->refresh();
        }

        try {
            broadcast(new ViewerCountUpdated($session->id, $session->viewer_count));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast viewer count on join', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new UserJoinedLiveSession($session->id, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar' => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'joined_at' => now()->toISOString(),
            ]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast UserJoinedLiveSession', ['error' => $e->getMessage()]);
        }

        $lastComments = LiveComment::with('user:id,name,profile_photo')
            ->where('live_session_id', $session->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($comment) => [
                'id' => $comment->id,
                'user_id' => $comment->user_id,
                'user_name' => $comment->user->name ?? 'Unknown',
                'user_avatar' => $comment->user->profile_photo
                    ? \App\Helpers\MediaHelper::getUrl($comment->user->profile_photo)
                    : null,
                'message' => $comment->message,
                'created_at' => $comment->created_at->toISOString(),
            ]);

        return [
            'session' => $this->formatSessionDetail($session),
            'last_comments' => $lastComments->values(),
        ];
    }

    public function leaveSession(int $id, $user): void
    {
        $session = LiveSession::findOrFail($id);

        $participant = LiveSessionParticipant::where('live_session_id', $session->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if ($participant) {
            $participant->update(['left_at' => now()]);

            if ($session->viewer_count > 0) {
                $session->decrement('viewer_count');
                $session->refresh();
            }
        }

        try {
            broadcast(new ViewerCountUpdated($session->id, $session->viewer_count));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast viewer count on leave', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new UserLeftLiveSession($session->id, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar' => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'left_at' => now()->toISOString(),
            ]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast UserLeftLiveSession', ['error' => $e->getMessage()]);
        }
    }

    public function addComment(int $sessionId, $user, string $message): array
    {
        $session = LiveSession::findOrFail($sessionId);

        if ($session->status !== 'ongoing') {
            throw new \RuntimeException('Live session is not currently active');
        }

        $comment = LiveComment::create([
            'live_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => $message,
        ]);

        try {
            broadcast(new NewLiveComment($session->id, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar' => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'message' => $comment->message,
                'created_at' => $comment->created_at->toISOString(),
            ]))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast comment', ['error' => $e->getMessage()]);
        }

        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'created_at' => $comment->created_at->toISOString(),
        ];
    }

    public function getComments(int $sessionId, int $perPage = 50): array
    {
        $session = LiveSession::findOrFail($sessionId);

        $comments = LiveComment::with('user:id,name,profile_photo')
            ->where('live_session_id', $session->id)
            ->latest()
            ->paginate($perPage);

        $data = $comments->map(fn($comment) => [
            'id' => $comment->id,
            'user_id' => $comment->user_id,
            'user_name' => $comment->user->name ?? 'Unknown',
            'user_avatar' => $comment->user->profile_photo ? \App\Helpers\MediaHelper::getUrl($comment->user->profile_photo) : null,
            'message' => $comment->message,
            'created_at' => $comment->created_at->toISOString(),
        ]);

        return [
            'data' => $data->values(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'total_pages' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Astrologer-side methods
    // ─────────────────────────────────────────────────────────

    /**
     * Create a new live session for an astrologer.
     */
    public function createSession(int $astrologerId, array $data): array
    {
        $isInstant = filter_var($data['is_instant'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $liveSessionData = [
            'astrologer_id' => $astrologerId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $isInstant ? now() : $data['scheduled_at'],
            'session_type' => $data['session_type'],
            'status' => $isInstant ? 'ongoing' : 'upcoming',
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'max_participants' => $data['max_participants'] ?? 100,
        ];

        $liveSession = LiveSession::create($liveSessionData);

        if ($isInstant) {
            $freshSession = $liveSession->fresh(['astrologer.user']);
            try {
                broadcast(new LiveSessionStarted($freshSession));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast LiveSessionStarted on create', ['error' => $e->getMessage()]);
            }
            try {
                $this->notifyAllUsersAboutLive($freshSession);
            } catch (\Exception $e) {
                Log::error('Failed to notify users about live', ['error' => $e->getMessage()]);
            }
        }

        return $this->formatSession($liveSession);
    }

    /**
     * Get paginated sessions for an astrologer with optional filtering.
     */
    public function getAstrologerSessions(int $astrologerId, string $filter = 'all', int $perPage = 15): array
    {
        if ($filter === 'upcoming') {
            $sessions = LiveSession::where('astrologer_id', $astrologerId)
                ->upcoming()
                ->paginate($perPage);

            return [
                'data' => $sessions->map(fn($s) => $this->formatSession($s))->values(),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'total_pages' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ];
        }

        if ($filter === 'completed') {
            $sessions = LiveSession::where('astrologer_id', $astrologerId)
                ->completed()
                ->paginate($perPage);

            return [
                'data' => $sessions->map(fn($s) => $this->formatSession($s))->values(),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'total_pages' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ];
        }

        // Return all, separated by status
        $upcoming = LiveSession::where('astrologer_id', $astrologerId)
            ->upcoming()
            ->get()
            ->map(fn($s) => $this->formatSession($s));

        $completedQuery = LiveSession::where('astrologer_id', $astrologerId)
            ->completed()
            ->paginate($perPage);

        return [
            'upcoming' => [
                'data' => $upcoming->values(),
                'total' => $upcoming->count(),
            ],
            'completed' => [
                'data' => $completedQuery->map(fn($s) => $this->formatSession($s))->values(),
                'pagination' => [
                    'current_page' => $completedQuery->currentPage(),
                    'total_pages' => $completedQuery->lastPage(),
                    'per_page' => $completedQuery->perPage(),
                    'total' => $completedQuery->total(),
                ],
            ],
        ];
    }

    /**
     * Get a specific session by ID for an astrologer.
     */
    public function getAstrologerSession(int $astrologerId, int $sessionId): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        return $this->formatSession($liveSession);
    }

    /**
     * Update an existing session.
     */
    public function updateSession(int $astrologerId, int $sessionId, array $data): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if ($liveSession->scheduled_at < now() && array_key_exists('scheduled_at', $data)) {
            throw new \Exception('Cannot update scheduled time for past sessions');
        }

        $allowedFields = [
            'title', 'description', 'scheduled_at', 'session_type',
            'status', 'duration_minutes', 'max_participants',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $liveSession->update($updateData);

        return $this->formatSession($liveSession);
    }

    /**
     * Delete a session.
     */
    public function deleteSession(int $astrologerId, int $sessionId): string
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if ($liveSession->status === 'ongoing') {
            throw new \Exception('Cannot delete an ongoing live session');
        }

        $title = $liveSession->title;
        $liveSession->delete();

        return $title;
    }

    /**
     * Start an upcoming session (go live).
     */
    public function startSession(int $astrologerId, int $sessionId): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if ($liveSession->status !== 'upcoming') {
            throw new \Exception('Only upcoming sessions can be started');
        }

        $liveSession->update([
            'status' => 'ongoing',
        ]);

        $freshSession = $liveSession->fresh(['astrologer.user']);
        try {
            broadcast(new LiveSessionStarted($freshSession));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast LiveSessionStarted on start', ['error' => $e->getMessage()]);
        }
        try {
            $this->notifyAllUsersAboutLive($freshSession);
        } catch (\Exception $e) {
            Log::error('Failed to notify users about live', ['error' => $e->getMessage()]);
        }

        return $this->formatSession($freshSession);
    }

    /**
     * Stop an ongoing session (end stream).
     */
    public function stopSession(int $astrologerId, int $sessionId): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if ($liveSession->status !== 'ongoing') {
            throw new \Exception('Only ongoing sessions can be stopped');
        }

        $existingRoomUuid = $liveSession->room_uuid;
        if ($existingRoomUuid) {
            try {
                $this->liveKit->deleteRoom($existingRoomUuid);
            } catch (\Exception $e) {
                Log::error('Failed to delete LiveKit room during stop', [
                    'room' => $existingRoomUuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            broadcast(new AstrologerMediaStatusChanged(
                $liveSession->id,
                ['live_session_id' => $liveSession->id, 'user_id' => $liveSession->astrologer?->user_id, 'type' => 'camera', 'status' => 'off']
            ));
            broadcast(new AstrologerMediaStatusChanged(
                $liveSession->id,
                ['live_session_id' => $liveSession->id, 'user_id' => $liveSession->astrologer?->user_id, 'type' => 'audio', 'status' => 'off']
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
            broadcast(new LiveSessionEnded($freshSession));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast LiveSessionEnded', ['error' => $e->getMessage()]);
        }

        return $this->formatSession($freshSession);
    }

    /**
     * Get the currently ongoing session for an astrologer.
     */
    public function getCurrentSession(int $astrologerId): ?array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('status', 'ongoing')
            ->first();

        if (!$liveSession) {
            return null;
        }

        return $this->formatSession($liveSession);
    }

    /**
     * Start LiveKit broadcast for an ongoing session.
     */
    public function startBroadcast(int $astrologerId, int $sessionId): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if ($liveSession->status !== 'ongoing') {
            throw new \Exception('Only ongoing sessions can start broadcasting');
        }

        if ($liveSession->room_uuid && $liveSession->is_broadcasting) {
            $astrologerUserId = $liveSession->astrologer?->user_id;
            $token = $this->liveKit->generateToken(
                $liveSession->room_uuid,
                'astro_' . $astrologerUserId,
                canPublish: true
            );

            return [
                'already_active' => true,
                'data' => [
                    'livekit_ws_url' => $this->liveKit->getWsUrl(),
                    'room_uuid' => $liveSession->room_uuid,
                    'token' => $token,
                ],
            ];
        }

        $roomName = 'live_' . $liveSession->id;

        try {
            $this->liveKit->createRoom($roomName);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $liveSession->update([
            'room_uuid' => $roomName,
            'is_broadcasting' => true,
        ]);

        // Use the astrologer's user_id from the session relation
        $astrologerUserId = $liveSession->astrologer?->user_id;

        LiveSessionParticipant::updateOrCreate(
            [
                'live_session_id' => $liveSession->id,
                'user_id' => $astrologerUserId,
                'role' => 'astrologer',
            ],
            [
                'livekit_identity' => 'astro_' . $astrologerUserId,
                'joined_at' => now(),
                'left_at' => null,
            ]
        );

        $token = $this->liveKit->generateToken(
            $roomName,
            'astro_' . $astrologerUserId,
            canPublish: true
        );

        try {
            broadcast(new AstrologerBroadcastStarted($liveSession->fresh()));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast AstrologerBroadcastStarted', ['error' => $e->getMessage()]);
        }

        return [
            'already_active' => false,
            'data' => [
                'livekit_ws_url' => $this->liveKit->getWsUrl(),
                'room_uuid' => $roomName,
                'token' => $token,
            ],
        ];
    }

    /**
     * Stop LiveKit broadcast without ending the session.
     */
    public function stopBroadcast(int $astrologerId, int $sessionId): void
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if (!$liveSession->is_broadcasting || !$liveSession->room_uuid) {
            // Already stopped or not broadcasting, treat as success (idempotent)
            return;
        }

        try {
            $this->liveKit->deleteRoom($liveSession->room_uuid);
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
            broadcast(new AstrologerMediaStatusChanged(
                $liveSession->id,
                ['live_session_id' => $liveSession->id, 'user_id' => $liveSession->astrologer?->user_id, 'type' => 'camera', 'status' => 'off']
            ));
            broadcast(new AstrologerMediaStatusChanged(
                $liveSession->id,
                ['live_session_id' => $liveSession->id, 'user_id' => $liveSession->astrologer?->user_id, 'type' => 'audio', 'status' => 'off']
            ));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast media status on broadcast stop', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update astrologer media status (camera/audio on/off).
     */
    public function updateMediaStatus(int $astrologerId, int $sessionId, string $type, string $status): array
    {
        $liveSession = LiveSession::where('astrologer_id', $astrologerId)
            ->where('id', $sessionId)
            ->first();

        if (!$liveSession) {
            throw new \Exception('Live session not found');
        }

        if (!$liveSession->is_broadcasting) {
            throw new \Exception('No active broadcast');
        }

        $boolStatus = $status === 'on';

        if ($type === 'camera') {
            $liveSession->update(['is_camera_on' => $boolStatus]);
        } elseif ($type === 'audio') {
            $liveSession->update(['is_audio_on' => $boolStatus]);
        }

        try {
            broadcast(new AstrologerMediaStatusChanged(
                $liveSession->id,
                [
                    'live_session_id' => $liveSession->id,
                    'user_id' => $liveSession->astrologer?->user_id,
                    'type' => $type,
                    'status' => $status,
                ]
            ));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast media status', ['error' => $e->getMessage()]);
        }

        $fresh = $liveSession->fresh();

        return [
            'live_session_id' => $fresh->id,
            'is_camera_on' => $fresh->is_camera_on,
            'is_audio_on' => $fresh->is_audio_on,
        ];
    }

    /**
     * Send notification to all users when astrologer goes live.
     */
    private function notifyAllUsersAboutLive(LiveSession $liveSession): void
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

                User::chunk(100, function ($users) use ($title, $body, $meta) {
                    foreach ($users as $user) {
                        NotificationHelper::send($user->id, $title, $body, $meta);
                    }
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send live notifications: ' . $e->getMessage());
        }
    }

    /**
     * Format live session response for astrologer.
     */
    private function formatSession(LiveSession $session): array
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

    private function formatSessionListItem(LiveSession $session): array
    {
        $astrologer = $session->astrologer;
        $astrologerUser = $astrologer?->user;

        return [
            'id' => $session->id,
            'title' => $session->title,
            'astrologer' => $astrologer ? [
                'id' => $astrologer->user_id,
                'name' => $astrologerUser?->name,
                'profile_photo' => $astrologerUser?->profile_photo
                    ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo)
                    : $astrologer?->profile_photo,
            ] : null,
            'is_broadcasting' => $session->is_broadcasting,
            'is_camera_on' => $session->is_camera_on ?? false,
            'is_audio_on' => $session->is_audio_on ?? false,
            'viewer_count' => $session->viewer_count,
        ];
    }

    private function formatSessionDetail(LiveSession $session): array
    {
        $astrologer = $session->astrologer;
        $astrologerUser = $astrologer?->user;

        return [
            'id' => $session->id,
            'title' => $session->title,
            'description' => $session->description,
            'session_type' => $session->session_type,
            'status' => $session->status,
            'is_broadcasting' => $session->is_broadcasting,
            'is_camera_on' => $session->is_camera_on ?? false,
            'is_audio_on' => $session->is_audio_on ?? false,
            'viewer_count' => $session->viewer_count,
            'astrologer' => $astrologer ? [
                'id' => $astrologer->user_id,
                'name' => $astrologerUser?->name,
                'profile_photo' => $astrologerUser?->profile_photo
                    ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo)
                    : $astrologer?->profile_photo,
                'gender' => $astrologerUser?->gender,
                'date_of_birth' => $astrologer->date_of_birth?->format('Y-m-d'),
            ] : null,
        ];
    }
}
