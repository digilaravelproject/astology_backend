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
        'chat_message_sound',
        'chat_request_sound',
        'call_sound',
        'live_stream_sound',
        'call_channel_id',
        'chat_channel_id',
        'live_channel_id',
        'default_channel_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'chat_message_sound' => 'boolean',
        'chat_request_sound' => 'boolean',
        'call_sound' => 'boolean',
        'live_stream_sound' => 'boolean',
    ];

    /**
     * Retrieve current active FCM settings (cached).
     */
    public static function current(): self
    {
        return Cache::remember('admin_fcm_setting:singleton', 3600, function () {
            try {
                $setting = self::find(1);
                if ($setting) {
                    return $setting;
                }
            } catch (\Throwable $e) {
                // Ignore and proceed to create
            }

            $defaults = [
                'project_id' => null,
                'service_account_json_path' => null,
                'is_active' => true,
                'default_sound' => 'default',
                'chat_message_sound' => false,
                'chat_request_sound' => true,
                'call_sound' => true,
                'live_stream_sound' => true,
                'call_channel_id' => 'call_channel',
                'chat_channel_id' => 'chat_channel',
                'default_channel_id' => 'astology_notifications',
            ];

            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('admin_fcm_settings', 'live_channel_id')) {
                    $defaults['live_channel_id'] = 'live_session_channel';
                }
            } catch (\Throwable $e) {}

            return self::firstOrCreate(['id' => 1], $defaults);
        });
    }

    /**
     * Safe accessor for live_channel_id
     */
    public function getLiveChannelIdAttribute($value): string
    {
        return $value ?: 'live_session_channel';
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
