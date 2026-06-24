<?php

namespace App\Models;

use App\Helpers\MediaHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        'chat_enabled',
        'call_enabled',
        'video_call_enabled',
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
        'chat_enabled' => 'boolean',
        'call_enabled' => 'boolean',
        'video_call_enabled' => 'boolean',
        'chat_rate_per_minute' => 'decimal:2',
        'call_rate_per_minute' => 'decimal:2',
        'video_call_rate_per_minute' => 'decimal:2',
        'po_at_5_enabled' => 'boolean',
        'po_at_5_rate_per_minute' => 'decimal:2',
        'po_at_5_sessions' => 'integer',
    ];

    protected $hidden = [];

    /**
     *  Check if the column is_chat_enabled exists
     */
    protected static $hasIsChatEnabledColumn = null;

    protected function hasIsChatEnabledColumn()
    {
        if (self::$hasIsChatEnabledColumn === null) {
            try {
                self::$hasIsChatEnabledColumn = Schema::hasColumn($this->getTable(), 'is_chat_enabled');
            } catch (\Exception $e) {
                self::$hasIsChatEnabledColumn = false;
            }
        }

        return self::$hasIsChatEnabledColumn;
    }

    // Accessors and Mutators for backward compatibility mapping
    public function getIsChatEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsChatEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        $this->attributes[$col] = $value;
    }

    public function getIsCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsCallEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        $this->attributes[$col] = $value;
    }

    public function getIsVideoCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsVideoCallEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        $this->attributes[$col] = $value;
    }

    public function getChatEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setChatEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        $this->attributes[$col] = $value;
    }

    public function getCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setCallEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        $this->attributes[$col] = $value;
    }

    public function getVideoCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';

        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setVideoCallEnabledAttribute($value)
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        $this->attributes[$col] = $value;
    }

    public function getProfilePhotoAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return ltrim(preg_replace('#^/?storage/#', '', $value), '/');
    }

    public function getIdProofAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return ltrim(preg_replace('#^/?storage/#', '', $value), '/');
    }

    public function getCertificateAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return ltrim(preg_replace('#^/?storage/#', '', $value), '/');
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return MediaHelper::getFullUrl($this->profile_photo);
    }

    public function getIdProofUrlAttribute(): ?string
    {
        return MediaHelper::getUrl($this->id_proof);
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return MediaHelper::getUrl($this->certificate);
    }

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

    public function priceIncreaseRequests()
    {
        return $this->hasMany(PriceIncreaseRequest::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'astrologer_offers')
            ->withPivot('id', 'status', 'activated_at', 'deactivated_at')
            ->withTimestamps();
    }

    public function getTotalBusyMinutesAttribute(): float
    {
        $callSeconds = (float) CallSession::where('provider_id', $this->user_id)
            ->whereIn('status', ['completed', 'approved'])
            ->sum('duration_seconds');

        $chatSeconds = (float) ChatSession::where('provider_id', $this->user_id)
            ->whereIn('status', ['completed', 'approved'])
            ->sum('duration_seconds');

        return ($callSeconds + $chatSeconds) / 60;
    }
}
