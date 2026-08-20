<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    use HasFactory;

    protected $table = 'user_blocks';

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
    ];

    /**
     * The user who initiated the block.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * The user who was blocked.
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
