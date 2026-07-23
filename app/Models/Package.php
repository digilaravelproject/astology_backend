<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_amount',
        'default_duration',
        'is_default',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'default_duration' => 'integer',
        'is_default' => 'boolean',
    ];
}
