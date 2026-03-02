<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Astrologer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'years_of_experience',
        'areas_of_expertise',
        'languages',
        'profile_photo',
        'bio',
        'id_proof',
        'certificate',
        'id_proof_number',
        'date_of_birth',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'status',
    ];

    protected $casts = [
        'areas_of_expertise' => 'array',
        'languages' => 'array',
        'date_of_birth' => 'date',
        'otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    /**
     * Get the user associated with the astrologer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
