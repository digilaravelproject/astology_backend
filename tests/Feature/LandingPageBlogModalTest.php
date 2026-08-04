<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageBlogModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_modal_payload_safely_renders_quotes_and_line_breaks(): void
    {
        Blog::create([
            'title' => 'Saturn\'s "Special" Transit',
            'content' => "<p>First line</p>\n<p>Reader's second line & guidance.</p>",
            'author' => "Acharya O'Neil",
            'type' => Blog::TYPE_ARTICLE,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-blog=', false);
        $response->assertSee('JSON.parse($el.dataset.blog)', false);
        $response->assertSee('Saturn&#039;s', false);
        $response->assertSee('&quot;Special&quot;', false);
    }
}
