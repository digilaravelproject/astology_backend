<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminFcmSetting extends Model
{
    use HasFactory;

    protected $table = 'admin_fcm_settings';

    protected $fillable = [
        'project_id',
        'service_account_json_path',
        'is_active',
        'default_sound',
        'call_channel_id',
        'chat_channel_id',
        'default_channel_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retrieve current active FCM settings (cached).
     */
    public static function current(): self
    {
        return Cache::remember('admin_fcm_setting:singleton', 3600, function () {
            return self::firstOrCreate(
                ['id' => 1],
                [
                    'project_id' => null,
                    'service_account_json_path' => null,
                    'is_active' => true,
                    'default_sound' => 'default',
                    'call_channel_id' => 'call_channel',
                    'chat_channel_id' => 'chat_channel',
                    'default_channel_id' => 'astology_notifications',
                ]
            );
        });
    }

    /**
     * Clear cached settings upon updating.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('admin_fcm_setting:singleton');
            Cache::forget('fcm_oauth2_access_token');
        });

        static::deleted(function () {
            Cache::forget('admin_fcm_setting:singleton');
            Cache::forget('fcm_oauth2_access_token');
        });
    }
}
