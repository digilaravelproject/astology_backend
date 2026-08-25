<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundersWord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'language_id',
        'title',
        'message',
        'title_en',
        'message_en',
        'title_hi',
        'message_hi',
        'title_mr',
        'message_mr',
        'translations',
        'image',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'translations' => 'array',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return \App\Helpers\MediaHelper::getUrl($this->image);
    }

    /**
     * Get the language associated with this founder word.
     */
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get title for specific language with automatic fallback.
     */
    public function getTranslatedTitle(?string $code = 'en'): string
    {
        $code = strtolower($code ?? 'en');
        if ($code === 'hi' && !empty($this->title_hi)) {
            return $this->title_hi;
        }
        if ($code === 'mr' && !empty($this->title_mr)) {
            return $this->title_mr;
        }
        if ($code === 'en' && !empty($this->title_en)) {
            return $this->title_en;
        }

        // Check translations json if exists
        if (!empty($this->translations[$code]['title'])) {
            return $this->translations[$code]['title'];
        }

        return $this->title_en ?: ($this->title ?: '');
    }

    /**
     * Get message for specific language with automatic fallback.
     */
    public function getTranslatedMessage(?string $code = 'en'): string
    {
        $code = strtolower($code ?? 'en');
        if ($code === 'hi' && !empty($this->message_hi)) {
            return $this->message_hi;
        }
        if ($code === 'mr' && !empty($this->message_mr)) {
            return $this->message_mr;
        }
        if ($code === 'en' && !empty($this->message_en)) {
            return $this->message_en;
        }

        // Check translations json if exists
        if (!empty($this->translations[$code]['message'])) {
            return $this->translations[$code]['message'];
        }

        return $this->message_en ?: ($this->message ?: '');
    }
}
