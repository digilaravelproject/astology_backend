<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerDefaultMessage extends Model
{
    use HasFactory;

    protected $table = 'astrologer_default_messages';

    protected $fillable = [
        'astrologer_id',
        'title',
        'content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the astrologer user associated with this default message template.
     */
    public function astrologer()
    {
        return $this->belongsTo(User::class, 'astrologer_id');
    }
}
