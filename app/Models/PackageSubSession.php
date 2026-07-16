<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSubSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_purchase_id',
        'mode',
        'chat_session_id',
        'call_session_id',
        'started_at',
        'ended_at',
        'duration_used',
    ];

    protected $casts = [
        'started_at'      => 'datetime',
        'ended_at'        => 'datetime',
        'duration_used'   => 'integer',
        'chat_session_id' => 'integer',
        'call_session_id' => 'integer',
    ];

    public function purchase()
    {
        return $this->belongsTo(PackagePurchase::class, 'package_purchase_id');
    }

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function callSession()
    {
        return $this->belongsTo(CallSession::class, 'call_session_id');
    }
}
