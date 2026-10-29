<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AstrologerGallery extends Model
{
    protected $table = 'astrologer_galleries';

    protected $fillable = [
        'astrologer_id',
        'image_path',
        'status',
        'is_visible',
        'remarks',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'image',
        'is_approved',
        'approval_status',
    ];

    public function getStatusAttribute($value): string
    {
        return $value === 'active' ? 'approved' : ($value ?? 'pending');
    }

    public function getImageUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getFullUrl($this->image_path);
    }

    public function getImageAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getFullUrl($this->image_path);
    }

    public function getIsApprovedAttribute(): bool
    {
        return in_array($this->attributes['status'] ?? null, ['active', 'approved']);
    }

    public function getApprovalStatusAttribute(): string
    {
        $status = $this->attributes['status'] ?? 'pending';
        return in_array($status, ['active', 'approved']) ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending');
    }

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }
}
