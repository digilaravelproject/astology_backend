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
        Schema::table('live_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('live_sessions', 'is_scheduled_notified')) {
                $table->boolean('is_scheduled_notified')->default(false)->after('viewer_count');
            }
            if (!Schema::hasColumn('live_sessions', 'is_reminder_notified')) {
                $table->boolean('is_reminder_notified')->default(false)->after('is_scheduled_notified');
            }
            if (!Schema::hasColumn('live_sessions', 'is_live_notified')) {
                $table->boolean('is_live_notified')->default(false)->after('is_reminder_notified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'is_scheduled_notified',
                'is_reminder_notified',
                'is_live_notified',
            ]);
        });
    }
};
