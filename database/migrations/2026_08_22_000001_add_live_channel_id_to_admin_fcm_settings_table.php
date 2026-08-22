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
        if (Schema::hasTable('admin_fcm_settings')) {
            Schema::table('admin_fcm_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_fcm_settings', 'live_channel_id')) {
                    $table->string('live_channel_id')->default('live_session_channel')->after('chat_channel_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_fcm_settings')) {
            Schema::table('admin_fcm_settings', function (Blueprint $table) {
                if (Schema::hasColumn('admin_fcm_settings', 'live_channel_id')) {
                    $table->dropColumn('live_channel_id');
                }
            });
        }
    }
};
