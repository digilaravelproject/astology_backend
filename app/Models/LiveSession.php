<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSession extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'astrologer_id',
        'title',
        'description',
        'scheduled_at',
        'session_type',
        'status',
        'live_url',
        'duration_minutes',
        'max_participants',
        'current_participants',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the astrologer that owns this live session.
     */
    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
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
}
