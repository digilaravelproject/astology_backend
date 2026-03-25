<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AstrologerReview extends Model
{
    protected $fillable = [
        'astrologer_id',
        'user_id',
        'rating',
        'review',
        'reply',
        'reply_at',
    ];

    protected $casts = [
        'reply_at' => 'datetime',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
