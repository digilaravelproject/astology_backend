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
        if (Schema::hasTable('astrologers')) {
            Schema::table('astrologers', function (Blueprint $table) {
                // Fix po_at_5_enabled
                if (!Schema::hasColumn('astrologers', 'po_at_5_enabled')) {
                    if (Schema::hasColumn('astrologers', 'po5_enabled')) {
                        $table->renameColumn('po5_enabled', 'po_at_5_enabled');
                    } else {
                        $table->boolean('po_at_5_enabled')->default(false)->after('video_call_rate_per_minute');
                    }
                }

                // Fix po_at_5_rate_per_minute
                if (!Schema::hasColumn('astrologers', 'po_at_5_rate_per_minute')) {
                    if (Schema::hasColumn('astrologers', 'po5_user_rate')) {
                        $table->renameColumn('po5_user_rate', 'po_at_5_rate_per_minute');
                    } else {
                        $table->decimal('po_at_5_rate_per_minute', 8, 2)->default(5.00)->after('po_at_5_enabled');
                    }
                }

                // Fix po_at_5_sessions
                if (!Schema::hasColumn('astrologers', 'po_at_5_sessions')) {
                    if (Schema::hasColumn('astrologers', 'po5_sessions')) {
                        $table->renameColumn('po5_sessions', 'po_at_5_sessions');
                    } else {
                        $table->unsignedTinyInteger('po_at_5_sessions')->default(5)->after('po_at_5_rate_per_minute');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe hotfix rollback not required
    }
};
