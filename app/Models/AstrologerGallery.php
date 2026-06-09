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

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getUrl($this->image_path);
    }

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }
}
