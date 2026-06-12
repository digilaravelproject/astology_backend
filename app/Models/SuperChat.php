<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperChat extends Model
{
    protected $fillable = [
        'live_session_id',
        'user_id',
        'astrologer_id',
        'amount',
        'message',
        'transaction_status',
        'wallet_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
