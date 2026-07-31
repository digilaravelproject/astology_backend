<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_custom_static_page_type(): void
    {
        $response = $this->withoutMiddleware()->post(route('admin.static_pages.store'), [
            'type' => 'other',
            'custom_type' => 'Refund Policy',
            'title' => 'Refund Policy',
            'content' => '<p>Refund details</p>',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.static_pages.index'));
        $this->assertDatabaseHas('static_pages', [
            'type' => 'refund_policy',
            'title' => 'Refund Policy',
            'is_active' => true,
        ]);
    }

    public function test_custom_static_page_is_available_at_its_public_url(): void
    {
        StaticPage::create([
            'type' => 'refund_policy',
            'title' => 'Refund Policy',
            'content' => '<p>Refund details</p>',
            'is_active' => true,
        ]);

        $this->get(route('page.show', 'refund_policy'))
            ->assertOk()
            ->assertSee('Refund Policy')
            ->assertSee('Refund details');
    }

    public function test_active_database_pages_are_displayed_as_footer_links(): void
    {
        StaticPage::create([
            'type' => 'corporate_information',
            'title' => 'Corporate Information',
            'content' => '<p>Company details</p>',
            'is_active' => true,
        ]);

        StaticPage::create([
            'type' => 'hidden_page',
            'title' => 'Hidden Page',
            'content' => '<p>Hidden details</p>',
            'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Corporate Information')
            ->assertSee(route('page.show', 'corporate_information'))
            ->assertDontSee('Hidden Page');
    }
}
