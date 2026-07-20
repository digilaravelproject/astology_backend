<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAssistanceAstrologerLimit extends Model
{
    protected $fillable = [
        'astrologer_id',
        'date',
        'reply_count',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'reply_count' => 'integer',
    ];

    public function astrologer()
    {
        return $this->belongsTo(User::class, 'astrologer_id');
    }
}
