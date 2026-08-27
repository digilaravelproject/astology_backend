<?php

namespace App\Models;

use App\Helpers\MediaHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

/**
 * App\Models\Astrologer
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $years_of_experience
 * @property array|null $areas_of_expertise
 * @property array|null $languages
 * @property string|null $profile_photo
 * @property string|null $bio
 * @property string|null $id_proof
 * @property string|null $id_proof_number
 * @property string|null $certificate
 * @property string|null $gst_number
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string|null $otp
 * @property \Illuminate\Support\Carbon|null $otp_expires_at
 * @property \Illuminate\Support\Carbon|null $otp_verified_at
 * @property string $status
 * @property bool $is_online
 * @property bool $is_chat_enabled
 * @property bool $is_call_enabled
 * @property bool $is_video_call_enabled
 * @property bool $chat_enabled
 * @property bool $call_enabled
 * @property bool $video_call_enabled
 * @property float $chat_rate_per_minute
 * @property float $call_rate_per_minute
 * @property float $video_call_rate_per_minute
 * @property bool $po_at_5_enabled
 * @property float $po_at_5_rate_per_minute
 * @property int $po_at_5_sessions
 * @property \Illuminate\Support\Carbon|null $sleep_start_time
 * @property \Illuminate\Support\Carbon|null $sleep_end_time
 * @property int|null $sleep_duration_minutes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\AstrologerSkill|null $skill
 * @property-read \App\Models\AstrologerOtherDetail|null $otherDetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AstrologerCommunity[] $community
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AstrologerReview[] $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AstrologerBankAccount[] $bankAccounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AstrologerGallery[] $galleries
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LiveSession[] $liveSessions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Offer[] $offers
 *
 * @property-read string|null $profile_photo_url
 * @property-read string|null $id_proof_url
 * @property-read string|null $certificate_url
 * @property-read float $total_busy_minutes
 */
class Astrologer extends Model
{
    use HasFactory;

    // =========================================================================
    // CONSTANTS & ENUMS
    // =========================================================================

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    // =========================================================================
    // MASS ASSIGNMENT
    // =========================================================================

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Identification & Profile
        'user_id',
        'years_of_experience',
        'areas_of_expertise',
        'languages',
        'profile_photo',
        'bio',
        'date_of_birth',

        // Identity Verification & Compliance
        'id_proof',
        'id_proof_number',
        'certificate',
        'gst_number',

        // Auth & Verification
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'status',

        // Online & Channel Availability (New & Legacy columns)
        'is_online',
        'is_chat_enabled',
        'is_call_enabled',
        'is_video_call_enabled',
        'chat_enabled',
        'call_enabled',
        'video_call_enabled',

        // Per Minute Service Pricing
        'chat_rate_per_minute',
        'call_rate_per_minute',
        'video_call_rate_per_minute',

        // Promotional Campaigns & Sleeping Schedules
        'availability',
        'po_at_5_enabled',
        'po_at_5_rate_per_minute',
        'po_at_5_sessions',
        'sleep_start_time',
        'sleep_end_time',
        'sleep_duration_minutes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id_proof',
        'id_proof_number',
        'certificate',
        'gst_number',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // JSON & Arrays
        'areas_of_expertise'         => 'array',
        'languages'                  => 'array',
        'availability'               => 'array',

        // Dates & Times
        'date_of_birth'              => 'date',
        'otp_expires_at'             => 'datetime',
        'otp_verified_at'            => 'datetime',
        'sleep_start_time'           => 'datetime:H:i',
        'sleep_end_time'             => 'datetime:H:i',
        'sleep_duration_minutes'     => 'integer',

        // Service Channel Booleans
        'is_online'                  => 'boolean',
        'is_chat_enabled'            => 'boolean',
        'is_call_enabled'            => 'boolean',
        'is_video_call_enabled'      => 'boolean',
        'chat_enabled'               => 'boolean',
        'call_enabled'               => 'boolean',
        'video_call_enabled'         => 'boolean',

        // Rates & Financial Pricing
        'chat_rate_per_minute'       => 'decimal:2',
        'call_rate_per_minute'       => 'decimal:2',
        'video_call_rate_per_minute' => 'decimal:2',

        // Promotional Offers
        'po_at_5_enabled'            => 'boolean',
        'po_at_5_rate_per_minute'    => 'decimal:2',
        'po_at_5_sessions'           => 'integer',
    ];

    // =========================================================================
    // ELOQUENT RELATIONSHIPS
    // =========================================================================

    /**
     * Get the base User account associated with this astrologer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the skill metadata for the astrologer.
     */
    public function skill(): HasOne
    {
        return $this->hasOne(AstrologerSkill::class);
    }

    /**
     * Get additional biographical and onboarding details.
     */
    public function otherDetails(): HasOne
    {
        return $this->hasOne(AstrologerOtherDetail::class);
    }

    /**
     * Get followers / community members.
     */
    public function community(): HasMany
    {
        return $this->hasMany(AstrologerCommunity::class);
    }

    /**
     * Get only favorited community followers.
     */
    public function favoriteCommunity(): HasMany
    {
        return $this->hasMany(AstrologerCommunity::class)->where('is_liked', true);
    }

    /**
     * Get reviews and ratings submitted for this astrologer.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(AstrologerReview::class);
    }

    /**
     * Get registered verified phone numbers.
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(AstrologerPhoneNumber::class);
    }

    /**
     * Get verified bank accounts for payouts.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(AstrologerBankAccount::class);
    }

    /**
     * Get portfolio and gallery images.
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(AstrologerGallery::class);
    }

    /**
     * Get broadcast live streaming sessions.
     */
    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    /**
     * Get tariff/price increase requests submitted for admin approval.
     */
    public function priceIncreaseRequests(): HasMany
    {
        return $this->hasMany(PriceIncreaseRequest::class);
    }

    /**
     * Get active promotional offers assigned to this astrologer.
     */
    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'astrologer_offers')
            ->withPivot('id', 'status', 'activated_at', 'deactivated_at')
            ->withTimestamps();
    }

    // =========================================================================
    // BACKWARD-COMPATIBLE SERVICE CHANNEL ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Check if the column is_chat_enabled exists in schema.
     */
    protected function hasIsChatEnabledColumn(): bool
    {
        try {
            return Schema::hasColumn($this->getTable(), 'is_chat_enabled');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getIsChatEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsChatEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    public function getIsCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsCallEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    public function getIsVideoCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setIsVideoCallEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    public function getChatEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setChatEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_chat_enabled' : 'chat_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    public function getCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setCallEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_call_enabled' : 'call_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    public function getVideoCallEnabledAttribute(): bool
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        return (bool) ($this->attributes[$col] ?? false);
    }

    public function setVideoCallEnabledAttribute($value): void
    {
        $col = $this->hasIsChatEnabledColumn() ? 'is_video_call_enabled' : 'video_call_enabled';
        $this->attributes[$col] = (bool) $value;
    }

    // =========================================================================
    // MEDIA & FILE ACCESSORS
    // =========================================================================

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

    // =========================================================================
    // COMPUTED ANALYTICS ACCESSORS
    // =========================================================================

    /**
     * Compute total busy consultation minutes across chat and call sessions.
     */
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

    /**
     * Get real-time availability status string: "Engaged" | "Online" | "Offline".
     */
    public function getAvailabilityStatusAttribute(): string
    {
        if (!empty($this->attributes['is_busy']) || !empty($this->is_busy)) {
            return 'Engaged';
        }

        $isOnline = (bool) (
            ($this->attributes['is_chat_enabled'] ?? false) ||
            ($this->attributes['is_call_enabled'] ?? false) ||
            ($this->attributes['is_video_call_enabled'] ?? false) ||
            ($this->attributes['is_online'] ?? false)
        );

        return $isOnline ? 'Online' : 'Offline';
    }
}
