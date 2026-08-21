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
        Schema::table('package_sub_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('package_sub_sessions', 'chat_status')) {
                $table->string('chat_status', 20)->default('idle')->after('mode');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'call_status')) {
                $table->string('call_status', 20)->default('idle')->after('chat_status');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'session_state')) {
                $table->string('session_state', 20)->default('pending')->after('call_status');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'last_heartbeat_user')) {
                $table->timestamp('last_heartbeat_user')->nullable()->after('duration_used');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'last_heartbeat_astrologer')) {
                $table->timestamp('last_heartbeat_astrologer')->nullable()->after('last_heartbeat_user');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('last_heartbeat_astrologer');
            }
            if (!Schema::hasColumn('package_sub_sessions', 'pause_duration_seconds')) {
                $table->unsignedInteger('pause_duration_seconds')->default(0)->after('paused_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_sub_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'chat_status',
                'call_status',
                'session_state',
                'last_heartbeat_user',
                'last_heartbeat_astrologer',
                'paused_at',
                'pause_duration_seconds',
            ]);
        });
    }
};
