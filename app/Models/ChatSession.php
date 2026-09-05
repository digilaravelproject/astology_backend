<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'consumer_id',
        'provider_id',
        'session_type',
        'status',
        'started_at',
        'accepted_at',
        'ended_at',
        'duration_seconds',
        'rate_per_minute',
        'total_cost',
        'last_billed_at',
        'question',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'total_cost' => 'float',
        'rate_per_minute' => 'float',
    ];

    public function isPrepaid(): bool
    {
        return $this->session_type === 'prepaid';
    }

    public function isNormal(): bool
    {
        return $this->session_type === 'normal' || empty($this->session_type);
    }

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function packageSubSession()
    {
        return $this->hasOne(PackageSubSession::class, 'chat_session_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
