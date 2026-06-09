<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kundli extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'gender',
        'birth_date',
        'birth_time',
        'latitude',
        'longitude',
        'datetime',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'birth_time' => 'string',
        'datetime' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}