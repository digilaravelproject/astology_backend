<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to call_sessions
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'astrologer_earning')) {
                $table->decimal('astrologer_earning', 10, 2)->default(0.00)->after('total_cost');
            }
            if (!Schema::hasColumn('call_sessions', 'admin_commission')) {
                $table->decimal('admin_commission', 10, 2)->default(0.00)->after('astrologer_earning');
            }
        });

        // 2. Add columns to chat_sessions
        Schema::table('chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_sessions', 'astrologer_earning')) {
                $table->decimal('astrologer_earning', 10, 2)->default(0.00)->after('total_cost');
            }
            if (!Schema::hasColumn('chat_sessions', 'admin_commission')) {
                $table->decimal('admin_commission', 10, 2)->default(0.00)->after('astrologer_earning');
            }
        });

        // 3. Optional backfill for existing completed records
        $globalCommission = (float) (DB::table('settings')->where('key', 'global_commission_percentage')->value('value') ?? 40.00);
        $astroSharePct = max(0, min(100, 100 - $globalCommission));

        DB::table('call_sessions')
            ->where('total_cost', '>', 0)
            ->where('astrologer_earning', 0)
            ->update([
                'astrologer_earning' => DB::raw("ROUND(total_cost * {$astroSharePct} / 100, 2)"),
                'admin_commission'   => DB::raw("ROUND(total_cost * {$globalCommission} / 100, 2)"),
            ]);

        DB::table('chat_sessions')
            ->where('total_cost', '>', 0)
            ->where('astrologer_earning', 0)
            ->update([
                'astrologer_earning' => DB::raw("ROUND(total_cost * {$astroSharePct} / 100, 2)"),
                'admin_commission'   => DB::raw("ROUND(total_cost * {$globalCommission} / 100, 2)"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'admin_commission')) {
                $table->dropColumn('admin_commission');
            }
            if (Schema::hasColumn('call_sessions', 'astrologer_earning')) {
                $table->dropColumn('astrologer_earning');
            }
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('chat_sessions', 'admin_commission')) {
                $table->dropColumn('admin_commission');
            }
            if (Schema::hasColumn('chat_sessions', 'astrologer_earning')) {
                $table->dropColumn('astrologer_earning');
            }
        });
    }
};
