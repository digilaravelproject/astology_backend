<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class Message extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'chat_session_id',
        'reply_to_id',
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
        'reply_to_id' => 'integer',
        'chat_session_id' => 'integer',
    ];

    protected $appends = [
        'attachment_url',
        'attachment',
        'image',
        'image_url',
        'file_path',
        'media_url',
        'url',
        'is_image',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getFullUrl($this->attributes['attachment_url'] ?? null);
    }

    public function getAttachmentAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getImageAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getFilePathAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->attachment_url;
    }

    public function getIsImageAttribute(): bool
    {
        if ($this->type === 'image') {
            return true;
        }

        $url = $this->attributes['attachment_url'] ?? '';
        if ($url) {
            $pathLower = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
            return str_contains($pathLower, '.jpg')
                || str_contains($pathLower, '.jpeg')
                || str_contains($pathLower, '.png')
                || str_contains($pathLower, '.webp')
                || str_contains($pathLower, '.gif')
                || str_contains($pathLower, '.heic')
                || str_contains($pathLower, '.heif')
                || str_contains($pathLower, '.bmp');
        }

        return false;
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

    /**
     * Self-referential relationship to the replied/quoted message.
     */
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }
}
