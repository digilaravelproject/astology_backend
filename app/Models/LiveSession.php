<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasLocalTimezoneSerialization;

class LiveSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'astrologer_id',
        'title',
        'description',
        'scheduled_at',
        'session_type',
        'status',
        'live_url',
        'stream_key',
        'stream_url',
        'started_at',
        'ended_at',
        'duration_minutes',
        'max_participants',
        'current_participants',
        'viewer_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'viewer_count' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LiveComment::class);
    }

    public function superChats(): HasMany
    {
        return $this->hasMany(SuperChat::class);
    }

    /**
     * Scope: Get upcoming live sessions
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
                     ->where('scheduled_at', '>=', now())
                     ->orderBy('scheduled_at', 'asc');
    }

    /**
     * Scope: Get completed live sessions
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'cancelled'])
                     ->orderBy('scheduled_at', 'desc');
    }

    /**
     * Scope: Get by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getLiveUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }
        if ($this->stream_key) {
            return "rtmp://suryapathkundli.com/live";
        }
        return null;
    }

    public function getStreamUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }
        if ($this->stream_key) {
            return "https://suryapathkundli.com/live/" . $this->stream_key . ".m3u8";
        }
        return null;
    }
}
