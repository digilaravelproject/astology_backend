<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public const TYPE_ARTICLE = 'article';
    public const TYPE_NEWS = 'news';
    public const TYPE_UPDATE = 'update';
    public const TYPE_EDUCATION = 'education';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    public static function types()
    {
        return [
            self::TYPE_ARTICLE => 'Article',
            self::TYPE_NEWS => 'News',
            self::TYPE_UPDATE => 'Update',
            self::TYPE_EDUCATION => 'Education',
            self::TYPE_ANNOUNCEMENT => 'Announcement',
        ];
    }

    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'author',
        'type',
        'blog_image',
        'blog_tags',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'blog_tags' => 'array',
    ];
}
