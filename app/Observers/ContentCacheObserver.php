<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\FoundersWord;
use App\Models\Notice;
use App\Models\Plan;
use App\Models\Remedy;
use App\Models\StaticPage;
use Illuminate\Support\Facades\Cache;

class ContentCacheObserver
{
    /**
     * Invalidate Blog cache keys.
     */
    public function savedBlog(Blog $blog): void
    {
        Cache::forget("blogs:lang:{$blog->language_id}");
        Cache::forget("blog:detail:{$blog->id}");
    }

    public function deletedBlog(Blog $blog): void
    {
        Cache::forget("blogs:lang:{$blog->language_id}");
        Cache::forget("blog:detail:{$blog->id}");
    }

    /**
     * Invalidate Remedy cache keys.
     */
    public function savedRemedy(Remedy $remedy): void
    {
        Cache::forget("remedies:lang:{$remedy->language_id}");
        Cache::forget("remedy:detail:{$remedy->id}");
    }

    public function deletedRemedy(Remedy $remedy): void
    {
        Cache::forget("remedies:lang:{$remedy->language_id}");
        Cache::forget("remedy:detail:{$remedy->id}");
    }

    /**
     * Invalidate StaticPage cache keys.
     */
    public function savedStaticPage(StaticPage $page): void
    {
        Cache::forget('static_pages:all');
        Cache::forget("static_pages:{$page->type}");
    }

    public function deletedStaticPage(StaticPage $page): void
    {
        Cache::forget('static_pages:all');
        Cache::forget("static_pages:{$page->type}");
    }

    /**
     * Invalidate FoundersWord cache keys.
     */
    public function savedFoundersWord(FoundersWord $word): void
    {
        Cache::forget('founders_words:active');
        Cache::forget("founders_word:detail:{$word->id}");
    }

    public function deletedFoundersWord(FoundersWord $word): void
    {
        Cache::forget('founders_words:active');
        Cache::forget("founders_word:detail:{$word->id}");
    }

    /**
     * Invalidate Notice cache keys.
     */
    public function savedNotice(Notice $notice): void
    {
        Cache::forget('notices:all');
        Cache::forget('notices:all_u1');
        Cache::forget('notices:all_u0');
    }

    public function deletedNotice(Notice $notice): void
    {
        Cache::forget('notices:all');
        Cache::forget('notices:all_u1');
        Cache::forget('notices:all_u0');
    }

    /**
     * Invalidate Plan cache keys.
     */
    public function savedPlan(Plan $plan): void
    {
        Cache::forget('plans:active');
    }

    public function deletedPlan(Plan $plan): void
    {
        Cache::forget('plans:active');
    }
}
