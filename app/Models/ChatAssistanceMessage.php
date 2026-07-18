<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatAssistanceMessage extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'chat_assistance_session_id',
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
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getUrl($this->attributes['attachment_url'] ?? null);
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
}
