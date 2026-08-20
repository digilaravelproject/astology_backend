<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class ChatHistoryService
{
    /**
     * Retrieve chronologically accurate chat history for a session.
     *
     * In mobile chat apps (Flutter/WhatsApp-style), Page 1 MUST return
     * the MOST RECENT messages, ordered chronologically (oldest -> newest)
     * so that the chat screen renders the latest conversation without lag.
     *
     * @param int $sessionId
     * @param int $userId
     * @param int $perPage
     * @param string $scope 'session' (only this session) or 'conversation' (all past history between participants)
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public function getMessagesForSession(int $sessionId, int $userId, int $perPage = 30, string $scope = 'conversation'): LengthAwarePaginator
    {
        $session = ChatSession::findOrFail($sessionId);

        // Security check: Must be a participant of this session
        if ((int) $session->consumer_id !== (int) $userId && (int) $session->provider_id !== (int) $userId) {
            throw new Exception("You are not authorized to access this chat history.", 403);
        }

        $query = Message::query();

        if ($scope === 'session') {
            $query->where('chat_session_id', $sessionId);
        } else {
            // Find all historical session IDs between these two participants
            $sessionIds = ChatSession::where(function ($q) use ($session) {
                    $q->where('consumer_id', $session->consumer_id)
                      ->where('provider_id', $session->provider_id);
                })
                ->orWhere(function ($q) use ($session) {
                    $q->where('consumer_id', $session->provider_id)
                      ->where('provider_id', $session->consumer_id);
                })
                ->pluck('id');

            $query->whereIn('chat_session_id', $sessionIds);
        }

        // Fetch paginated messages sorted by newest first for slicing
        $paginator = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Reverse the items on current page so they display chronologically (oldest -> newest)
        $chronologicalItems = $paginator->getCollection()->reverse()->values();

        return new LengthAwarePaginator(
            $chronologicalItems,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Get unread message count for a user across a session or globally.
     *
     * @param int $userId
     * @param int|null $sessionId
     * @return int
     */
    public function getUnreadCount(int $userId, ?int $sessionId = null): int
    {
        $query = Message::where('receiver_id', $userId)
            ->where('is_read', false);

        if ($sessionId) {
            $query->where('chat_session_id', $sessionId);
        }

        return $query->count();
    }
}
