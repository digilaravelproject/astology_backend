<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAssistanceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_assistance_session_id',
        'event_name',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function chatAssistanceSession()
    {
        return $this->belongsTo(ChatAssistanceSession::class);
    }
}
