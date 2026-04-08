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
        return ChatSession::with(['consumer', 'provider'])
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->latest()
            ->paginate(15);
    }
}
