<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastNotification extends Model
{
    use HasFactory;

    protected $table = 'broadcast_notifications';

    protected $fillable = [
        'title',
        'body',
        'image_url',
        'target_type',
        'target_user_id',
        'click_action',
        'data_payload',
        'total_recipients',
        'successful_count',
        'failed_count',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'data_payload' => 'array',
        'total_recipients' => 'integer',
        'successful_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
