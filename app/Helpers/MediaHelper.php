<?php

namespace App\Helpers;

class MediaHelper
{
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's a full URL, extract the path after /storage/
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path, PHP_URL_PATH);
            if ($parsed) {
                $path = ltrim($parsed, '/');
            }
        }

        $path = ltrim($path, '/');

        $path = preg_replace('#^storage/#', '', $path);

        $path = preg_replace('#^public/#', '', $path);

        return $path;
    }

    public static function getFullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        $path = preg_replace('#^storage/#', '', $path);

        $path = preg_replace('#^public/#', '', $path);

        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $url = $appUrl . '/' . ltrim($url, '/');
        }

        return $url;
    }
}
