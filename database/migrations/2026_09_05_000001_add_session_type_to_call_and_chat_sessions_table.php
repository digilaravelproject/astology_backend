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
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'session_type')) {
                $table->enum('session_type', ['normal', 'prepaid', 'live'])
                    ->default('normal')
                    ->after('provider_id')
                    ->index();
            }
            if (!Schema::hasColumn('call_sessions', 'live_session_id')) {
                $table->unsignedBigInteger('live_session_id')
                    ->nullable()
                    ->after('session_type')
                    ->index();
            }
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_sessions', 'session_type')) {
                $table->enum('session_type', ['normal', 'prepaid'])
                    ->default('normal')
                    ->after('provider_id')
                    ->index();
            }
        });

        // Data Backfill: Mark existing sessions linked to package_sub_sessions as 'prepaid'
        try {
            if (Schema::hasTable('package_sub_sessions')) {
                DB::table('call_sessions')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('package_sub_sessions')
                            ->whereColumn('package_sub_sessions.call_session_id', 'call_sessions.id');
                    })
                    ->orWhere('rate_per_minute', '<=', 0)
                    ->update(['session_type' => 'prepaid']);

                DB::table('chat_sessions')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('package_sub_sessions')
                            ->whereColumn('package_sub_sessions.chat_session_id', 'chat_sessions.id');
                    })
                    ->orWhere('rate_per_minute', '<=', 0)
                    ->update(['session_type' => 'prepaid']);
            }
        } catch (\Throwable $e) {
            // Log or ignore during migration if data is empty
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'live_session_id')) {
                $table->dropColumn('live_session_id');
            }
            if (Schema::hasColumn('call_sessions', 'session_type')) {
                $table->dropColumn('session_type');
            }
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('chat_sessions', 'session_type')) {
                $table->dropColumn('session_type');
            }
        });
    }
};
