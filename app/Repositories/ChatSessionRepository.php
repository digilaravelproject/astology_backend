<?php

namespace App\Repositories;

use App\Models\ChatSession;

class ChatSessionRepository
{
    public function findById($id)
    {
        return ChatSession::with(['consumer', 'provider'])->find($id);
    }

    public function create(array $data)
    {
        return ChatSession::create($data);
    }

    public function update($id, array $data)
    {
        return ChatSession::where('id', $id)->update($data);
    }
    
    public function getActiveChatsByProvider($providerId)
    {
        return ChatSession::where('provider_id', $providerId)
            ->whereIn('status', ['accepted', 'ongoing'])
            ->get();
    }

    public function getSessionsByUserId($userId)
    {
        return ChatSession::with(['consumer', 'provider', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) use ($userId) {
                $query->where('receiver_id', $userId)->where('is_read', false);
            }])
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->latest()
            ->paginate(15);
    }

    public function getUserSessions($userId)
    {
        return ChatSession::with(['provider.astrologer', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) use ($userId) {
                $query->where('receiver_id', $userId)->where('is_read', false);
            }])
            ->where('consumer_id', $userId)
            ->latest()
            ->paginate(15);
    }

    public function getAstrologerSessions($userId)
    {
        return ChatSession::with(['consumer', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) use ($userId) {
                $query->where('receiver_id', $userId)->where('is_read', false);
            }])
            ->where('provider_id', $userId)
            ->latest()
            ->paginate(15);
    }
}
