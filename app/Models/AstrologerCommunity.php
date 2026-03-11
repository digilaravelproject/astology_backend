<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerCommunity extends Model
{
    use HasFactory;

    protected $table = 'astrologer_communities';

    protected $fillable = [
        'astrologer_id',
        'user_id',
        'is_liked',
        'liked_at',
    ];

    protected $casts = [
        'is_liked' => 'boolean',
        'liked_at' => 'datetime',
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
