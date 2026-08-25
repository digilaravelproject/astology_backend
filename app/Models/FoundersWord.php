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
     * Get title for any language code with automatic fallback to English.
     */
    public function getTranslatedTitle(?string $code = 'en'): string
    {
        $code = strtolower($code ?? 'en');

        // Check translations json
        if (!empty($this->translations[$code]['title'])) {
            return $this->translations[$code]['title'];
        }

        // Check direct property if exists
        $col = 'title_' . $code;
        if (!empty($this->$col)) {
            return $this->$col;
        }

        // Fallback to English translation
        if (!empty($this->translations['en']['title'])) {
            return $this->translations['en']['title'];
        }

        return $this->title_en ?: ($this->title ?: '');
    }

    /**
     * Get message for any language code with automatic fallback to English.
     */
    public function getTranslatedMessage(?string $code = 'en'): string
    {
        $code = strtolower($code ?? 'en');

        // Check translations json
        if (!empty($this->translations[$code]['message'])) {
            return $this->translations[$code]['message'];
        }

        // Check direct property if exists
        $col = 'message_' . $code;
        if (!empty($this->$col)) {
            return $this->$col;
        }

        // Fallback to English translation
        if (!empty($this->translations['en']['message'])) {
            return $this->translations['en']['message'];
        }

        return $this->message_en ?: ($this->message ?: '');
    }
}
