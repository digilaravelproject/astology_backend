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

        return Storage::disk('public')->url($path);
    }

    public static function getFullUrl(?string $path): ?string
    {
        return self::getUrl($path);
    }
}
