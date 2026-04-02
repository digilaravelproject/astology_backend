<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'phone',
        'city',
        'country',
        'profile_photo',
        'gender',
        'date_of_birth',
        'time_of_birth',
        'place_of_birth',
        'languages',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'profile_completed',
        'plan_id',
        'plan_started_at',
        'plan_expires_at',
        'is_online',
        'last_seen_at',
        'is_busy',
        'busy_session_id',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'time_of_birth' => 'datetime:H:i',
            'languages' => 'array',
            'password' => 'hashed',
            'profile_completed' => 'boolean',
            'plan_started_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'is_online' => 'boolean',
            'is_busy' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the astrologer profile associated with the user.
     */
    public function astrologer()
    {
        return $this->hasOne(\App\Models\Astrologer::class);
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallet()
    {
        return $this->hasOne(\App\Models\Wallet::class);
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\AppNotification::class);
    }

    /**
     * Get the community records where this user is a follower.
     */
    public function astrologerCommunityRecords()
    {
        return $this->hasMany(\App\Models\AstrologerCommunity::class);
    }

    /**
     * Get reviews made by this user.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\AstrologerReview::class);
    }

    public function initiatedCalls()
    {
        return $this->hasMany(\App\Models\CallSession::class, 'consumer_id');
    }

    public function receivedCalls()
    {
        return $this->hasMany(\App\Models\CallSession::class, 'provider_id');
    }

    public function initiatedChats()
    {
        return $this->hasMany(\App\Models\ChatSession::class, 'consumer_id');
    }

    public function receivedChats()
    {
        return $this->hasMany(\App\Models\ChatSession::class, 'provider_id');
    }
}
