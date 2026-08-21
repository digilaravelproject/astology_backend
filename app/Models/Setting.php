<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * In-memory runtime cache to eliminate repeated Redis/DB queries in same request lifecycle.
     */
    private static array $runtimeCache = [];

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$runtimeCache)) {
            return self::$runtimeCache[$key];
        }

        $val = Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }
            return self::castValue($setting->value, $setting->type);
        });

        self::$runtimeCache[$key] = $val;
        return $val;
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general', string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        unset(self::$runtimeCache[$key]);
        Cache::forget("setting:{$key}");
        Cache::forget("setting_group:{$group}");

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return Cache::rememberForever("setting_group:{$group}", function () use ($group) {
            return self::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    public static function clearRuntimeCache(): void
    {
        self::$runtimeCache = [];
    }

    private static function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'double', 'decimal' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }
}