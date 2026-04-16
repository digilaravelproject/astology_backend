<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AstrologerSkill;
use App\Models\AstrologerOtherDetail;
use App\Models\AstrologerCommunity;
use App\Models\AstrologerBankAccount;

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
        'is_online',
        'is_chat_enabled',
        'is_call_enabled',
        'is_video_call_enabled',
        'chat_rate_per_minute',
        'call_rate_per_minute',
        'video_call_rate_per_minute',
        'po_at_5_enabled',
        'po_at_5_rate_per_minute',
        'po_at_5_sessions',
        'sleep_start_time',
        'sleep_end_time',
        'sleep_duration_minutes',
    ];

    protected $casts = [
        'areas_of_expertise' => 'array',
        'languages' => 'array',
        'availability' => 'array',
        'date_of_birth' => 'date',
        'otp_expires_at' => 'datetime',
        'sleep_start_time' => 'datetime:H:i',
        'sleep_end_time' => 'datetime:H:i',
        'sleep_duration_minutes' => 'integer',
        'otp_verified_at' => 'datetime',
        'is_online' => 'boolean',
        'is_chat_enabled' => 'boolean',
        'is_call_enabled' => 'boolean',
        'is_video_call_enabled' => 'boolean',
        'chat_rate_per_minute' => 'decimal:2',
        'call_rate_per_minute' => 'decimal:2',
        'video_call_rate_per_minute' => 'decimal:2',
        'po_at_5_enabled' => 'boolean',
        'po_at_5_rate_per_minute' => 'decimal:2',
        'po_at_5_sessions' => 'integer',
    ];

    /**
     * Get the user associated with the astrologer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the skill details for the astrologer.
     */
    public function skill()
    {
        return $this->hasOne(AstrologerSkill::class);
    }

    /**
     * Get the other details for the astrologer.
     */
    public function otherDetails()
    {
        return $this->hasOne(AstrologerOtherDetail::class);
    }

    /**
     * Get the community members (followers) for the astrologer.
     */
    public function community()
    {
        return $this->hasMany(AstrologerCommunity::class);
    }

    /**
     * Get only the liked/favorite community members.
     */
    public function favoriteCommunity()
    {
        return $this->hasMany(AstrologerCommunity::class)->where('is_liked', true);
    }

    /**
     * Get reviews for this astrologer.
     */
    public function reviews()
    {
        return $this->hasMany(AstrologerReview::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(AstrologerPhoneNumber::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(AstrologerBankAccount::class);
    }

    public function galleries()
    {
        return $this->hasMany(AstrologerGallery::class);
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class);
    }
}
