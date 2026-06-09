<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class MediaHelper
{
    public static function getUrl(?string $path): ?string
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

        $url = Storage::disk('public')->url($path);

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $url = $appUrl . '/' . ltrim($url, '/');
        }

        return $url;
    }

    public static function getFullUrl(?string $path): ?string
    {
        return self::getUrl($path);
    }
}
