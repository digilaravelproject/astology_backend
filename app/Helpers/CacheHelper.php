<?php

namespace App\Helpers;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Calculate a randomized TTL with jitter offset (e.g. -80s to +80s)
     * to eliminate Cache Stampede / Thundering Herd when keys expire.
     *
     * @param int $baseSeconds Base TTL in seconds
     * @param int $minJitter Negative lower offset in seconds (default: -80)
     * @param int $maxJitter Positive upper offset in seconds (default: 80)
     * @return int Safe randomized TTL in seconds
     */
    public static function jitter(int $baseSeconds, int $minJitter = -80, int $maxJitter = 80): int
    {
        // For very short base TTLs (e.g. <= 30 seconds), scale jitter proportionally
        if ($baseSeconds <= 30) {
            $minJitter = max(-10, (int) round(-$baseSeconds * 0.3));
            $maxJitter = max(5, (int) round($baseSeconds * 0.4));
        }

        try {
            $offset = random_int($minJitter, $maxJitter);
        } catch (\Throwable) {
            $offset = mt_rand($minJitter, $maxJitter);
        }

        $finalTtl = $baseSeconds + $offset;

        // Ensure TTL is always at least 5 seconds
        return max(5, $finalTtl);
    }

    /**
     * Cache::remember with automatic jitter protection.
     *
     * @param string $key Cache key
     * @param int $baseSeconds Base expiration duration in seconds
     * @param Closure $callback Resolver callback
     * @param int $minJitter Minimum random jitter offset in seconds
     * @param int $maxJitter Maximum random jitter offset in seconds
     * @return mixed
     */
    public static function remember(
        string $key,
        int $baseSeconds,
        Closure $callback,
        int $minJitter = -80,
        int $maxJitter = 80
    ) {
        $ttl = self::jitter($baseSeconds, $minJitter, $maxJitter);
        return Cache::remember($key, $ttl, $callback);
    }
}
