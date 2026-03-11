<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerOtherDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'astrologer_id',
        'gender',
        'current_address',
        'bio',
        'date_of_birth',
        'website_link',
        'instagram_username',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }
}
