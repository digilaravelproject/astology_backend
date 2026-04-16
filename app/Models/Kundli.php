<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kundli extends Model
{
    protected $fillable = [
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
}
