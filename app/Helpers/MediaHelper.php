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
            if (str_contains($path, 'localhost') || str_contains($path, '127.0.0.1')) {
                $parsed = parse_url($path, PHP_URL_PATH);
                $path = $parsed ? ltrim($parsed, '/') : $path;
            } else {
                // Ensure HTTPS for mobile clients (Android blocks cleartext HTTP)
                if (str_starts_with($path, 'http://')) {
                    $path = 'https://' . substr($path, 7);
                }
                return $path;
            }
        }

        $path = ltrim($path, '/');

        $path = preg_replace('#^storage/#', '', $path);

        $path = preg_replace('#^public/#', '', $path);

        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            if (!app()->runningInConsole() && request()) {
                $reqHost = request()->getHttpHost();
                if ($reqHost && !str_contains($reqHost, 'localhost') && !str_contains($reqHost, '127.0.0.1')) {
                    $appUrl = request()->schemeAndHttpHost();
                }
            }
            $url = $appUrl . '/' . ltrim($url, '/');
        } elseif ($url && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))) {
            if (!app()->runningInConsole() && request()) {
                $reqHost = request()->getHttpHost();
                if ($reqHost && !str_contains($reqHost, 'localhost') && !str_contains($reqHost, '127.0.0.1')) {
                    $parsed = parse_url($url, PHP_URL_PATH);
                    $url = request()->schemeAndHttpHost() . ($parsed ? '/' . ltrim($parsed, '/') : '');
                }
            }
        }

        // Always enforce HTTPS for non-local URLs so mobile apps don't drop image rendering
        if ($url && str_starts_with($url, 'http://') && !str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1')) {
            $url = 'https://' . substr($url, 7);
        }

        return $url;
    }
}
