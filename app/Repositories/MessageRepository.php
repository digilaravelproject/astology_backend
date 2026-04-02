<?php

namespace App\Repositories;

use App\Models\Message;

class MessageRepository
{
    public function create(array $data)
    {
        return Message::create($data);
    }
    
    public function getMessagesBySession($sessionId)
    {
        return Message::where('chat_session_id', $sessionId)->orderBy('id', 'asc')->get();
    }
}
