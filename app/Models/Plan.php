<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'features',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
