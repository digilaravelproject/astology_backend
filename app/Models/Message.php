<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class Message extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'chat_session_id',
        'sender_id',
        'receiver_id',
        'message',
        'attachment_url',
        'type',
        'is_read',
        'is_delivered',
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

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
