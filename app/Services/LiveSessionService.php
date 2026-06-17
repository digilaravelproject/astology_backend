<?php

namespace App\Services;

use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveComment;
use App\Events\NewLiveComment;
use App\Events\UserJoinedLiveSession;
use App\Events\UserLeftLiveSession;
use App\Events\ViewerCountUpdated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

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
            ]));
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
