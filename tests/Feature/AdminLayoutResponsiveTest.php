<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_layout_contains_mobile_shell_and_branded_logo(): void
    {
        $this->withoutMiddleware()
            ->get(route('admin.static_pages.index'))
            ->assertOk()
            ->assertSee('admin-main', false)
            ->assertSee('admin-content', false)
            ->assertSee(asset('images/logo.jpeg'), false)
            ->assertSee('Astology Premium');
    }
}
