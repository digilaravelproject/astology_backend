<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IceCandidate extends Model
{
    protected $fillable = [
        'call_session_id',
        'sender_id',
        'receiver_id',
        'candidate',
    ];

    public function callSession()
    {
        return $this->belongsTo(CallSession::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
