<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ResponsiveMarkupTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_shared_admin_layout_has_mobile_overflow_protection(): void
    {
        $layout = file_get_contents($this->projectRoot . '/resources/views/admin/layouts/app.blade.php');

        $this->assertStringContainsString('max-width: calc(100vw - 24px)', $layout);
        $this->assertStringContainsString('table { min-width: 680px; }', $layout);
        $this->assertStringContainsString("sidebarOpen ? 'translate-x-0", $layout);
    }

    public function test_login_and_footer_include_requested_branding_and_layout(): void
    {
        $login = file_get_contents($this->projectRoot . '/resources/views/admin/auth/login.blade.php');
        $footer = file_get_contents($this->projectRoot . '/resources/views/layouts/footer.blade.php');

        $this->assertStringContainsString("asset('images/logo.jpg')", $login);
        $this->assertStringContainsString('grid grid-cols-2 gap-x-6', $footer);
    }

    public function test_every_admin_content_page_inherits_the_responsive_layout(): void
    {
        $adminViews = $this->projectRoot . '/resources/views/admin';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminViews));

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (str_ends_with($path, '/layouts/app.blade.php') || str_ends_with($path, '/auth/login.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $this->assertStringContainsString(
                "@extends('admin.layouts.app')",
                $contents,
                "Admin view does not inherit the responsive layout: {$path}"
            );
        }
    }

    public function test_admin_theme_uses_the_logo_palette_without_legacy_pink_or_teal(): void
    {
        $themeFiles = [
            $this->projectRoot . '/resources/css/app.css',
            $this->projectRoot . '/tailwind.config.js',
            $this->projectRoot . '/resources/views/admin/auth/login.blade.php',
        ];

        foreach ($themeFiles as $themeFile) {
            $contents = strtolower(file_get_contents($themeFile));

            $this->assertStringContainsString('#c40000', $contents);
            $this->assertStringNotContainsString('#d63384', $contents);
            $this->assertStringNotContainsString('#f95a8f', $contents);
            $this->assertStringNotContainsString('#4ecdc4', $contents);
        }
    }
}
