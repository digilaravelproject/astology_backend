<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_fcm_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_fcm_settings', 'chat_message_sound')) {
                $table->boolean('chat_message_sound')->default(false)->after('default_sound');
            }
            if (!Schema::hasColumn('admin_fcm_settings', 'chat_request_sound')) {
                $table->boolean('chat_request_sound')->default(true)->after('chat_message_sound');
            }
            if (!Schema::hasColumn('admin_fcm_settings', 'call_sound')) {
                $table->boolean('call_sound')->default(true)->after('chat_request_sound');
            }
            if (!Schema::hasColumn('admin_fcm_settings', 'live_stream_sound')) {
                $table->boolean('live_stream_sound')->default(true)->after('call_sound');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_fcm_settings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('admin_fcm_settings', 'chat_message_sound')) {
                $columns[] = 'chat_message_sound';
            }
            if (Schema::hasColumn('admin_fcm_settings', 'chat_request_sound')) {
                $columns[] = 'chat_request_sound';
            }
            if (Schema::hasColumn('admin_fcm_settings', 'call_sound')) {
                $columns[] = 'call_sound';
            }
            if (Schema::hasColumn('admin_fcm_settings', 'live_stream_sound')) {
                $columns[] = 'live_stream_sound';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
