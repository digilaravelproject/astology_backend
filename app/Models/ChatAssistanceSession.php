<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatAssistanceSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'consumer_id',
        'provider_id',
    ];

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatAssistanceMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatAssistanceMessage::class, 'chat_assistance_session_id')->latestOfMany();
    }

    public function events()
    {
        return $this->hasMany(ChatAssistanceEvent::class);
    }
}
