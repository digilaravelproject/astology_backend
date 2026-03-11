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
        'gender',
        'date_of_birth',
        'time_of_birth',
        'place_of_birth',
        'languages',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'profile_completed',
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
        ];
    }

    /**
     * Get the astrologer profile associated with the user.
     */
    public function astrologer()
    {
        return $this->hasOne(\App\Models\Astrologer::class);
    }

    /**
     * Get the community records where this user is a follower.
     */
    public function astrologerCommunityRecords()
    {
        return $this->hasMany(\App\Models\AstrologerCommunity::class);
    }
}
