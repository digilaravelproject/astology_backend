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

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }
}
