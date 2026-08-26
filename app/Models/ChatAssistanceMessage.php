<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatAssistanceMessage extends Model
{
    use HasLocalTimezoneSerialization;

    protected $touches = ['chatAssistanceSession'];

    protected $fillable = [
        'chat_assistance_session_id',
        'reply_to_id',
        'sender_id',
        'receiver_id',
        'message',
        'attachment_url',
        'type',
        'is_read',
        'is_delivered',
        'call_session_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_delivered' => 'boolean',
        'reply_to_id' => 'integer',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getFullUrl($this->attributes['attachment_url'] ?? null);
    }

    public function chatAssistanceSession()
    {
        return $this->belongsTo(ChatAssistanceSession::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function callSession()
    {
        return $this->belongsTo(CallSession::class, 'call_session_id');
    }

    /**
     * Self-referential relationship to the replied/quoted message.
     */
    public function replyTo()
    {
        return $this->belongsTo(ChatAssistanceMessage::class, 'reply_to_id');
    }
}
