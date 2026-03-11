<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'astrologer_id',
        'category',
        'primary_skills',
        'all_skills',
        'languages',
        'experience_years',
        'daily_contribution_hours',
        'heard_about',
    ];

    protected $casts = [
        'primary_skills' => 'array',
        'all_skills' => 'array',
        'languages' => 'array',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }
}
