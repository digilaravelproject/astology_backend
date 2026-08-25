<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the blogs associated with the language.
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Get the remedies associated with the language.
     */
    public function remedies()
    {
        return $this->hasMany(Remedy::class);
    }

    /**
     * Get the founder words associated with the language.
     */
    public function founderWords()
    {
        return $this->hasMany(FoundersWord::class);
    }
}
