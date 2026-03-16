<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'description',
        'video_url',
        'thumbnail_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
