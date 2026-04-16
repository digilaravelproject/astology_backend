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
        if (!Schema::hasTable('astrologers')) {
            return;
        }

        Schema::table('astrologers', function (Blueprint $table) {
            // Rename chat_rate to chat_rate_per_minute
            if (Schema::hasColumn('astrologers', 'chat_rate') && !Schema::hasColumn('astrologers', 'chat_rate_per_minute')) {
                $table->renameColumn('chat_rate', 'chat_rate_per_minute');
            }

            // Rename call_rate to call_rate_per_minute
            if (Schema::hasColumn('astrologers', 'call_rate') && !Schema::hasColumn('astrologers', 'call_rate_per_minute')) {
                $table->renameColumn('call_rate', 'call_rate_per_minute');
            }

            // Rename video_call_rate to video_call_rate_per_minute
            if (Schema::hasColumn('astrologers', 'video_call_rate') && !Schema::hasColumn('astrologers', 'video_call_rate_per_minute')) {
                $table->renameColumn('video_call_rate', 'video_call_rate_per_minute');
            }

            // Rename po5_enabled to po_at_5_enabled
            if (Schema::hasColumn('astrologers', 'po5_enabled') && !Schema::hasColumn('astrologers', 'po_at_5_enabled')) {
                $table->renameColumn('po5_enabled', 'po_at_5_enabled');
            }

            // Rename po5_user_rate to po_at_5_rate_per_minute
            if (Schema::hasColumn('astrologers', 'po5_user_rate') && !Schema::hasColumn('astrologers', 'po_at_5_rate_per_minute')) {
                $table->renameColumn('po5_user_rate', 'po_at_5_rate_per_minute');
            }

            // Rename po5_astrologer_rate if it exists, to match naming convention
            if (Schema::hasColumn('astrologers', 'po5_astrologer_rate') && !Schema::hasColumn('astrologers', 'po_at_5_astrologer_rate')) {
                $table->renameColumn('po5_astrologer_rate', 'po_at_5_astrologer_rate');
            }

            // Rename po5_sessions to po_at_5_sessions
            if (Schema::hasColumn('astrologers', 'po5_sessions') && !Schema::hasColumn('astrologers', 'po_at_5_sessions')) {
                $table->renameColumn('po5_sessions', 'po_at_5_sessions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('astrologers')) {
            return;
        }

        Schema::table('astrologers', function (Blueprint $table) {
            // Reverse: chat_rate_per_minute to chat_rate
            if (Schema::hasColumn('astrologers', 'chat_rate_per_minute') && !Schema::hasColumn('astrologers', 'chat_rate')) {
                $table->renameColumn('chat_rate_per_minute', 'chat_rate');
            }

            // Reverse: call_rate_per_minute to call_rate
            if (Schema::hasColumn('astrologers', 'call_rate_per_minute') && !Schema::hasColumn('astrologers', 'call_rate')) {
                $table->renameColumn('call_rate_per_minute', 'call_rate');
            }

            // Reverse: video_call_rate_per_minute to video_call_rate
            if (Schema::hasColumn('astrologers', 'video_call_rate_per_minute') && !Schema::hasColumn('astrologers', 'video_call_rate')) {
                $table->renameColumn('video_call_rate_per_minute', 'video_call_rate');
            }

            // Reverse: po_at_5_enabled to po5_enabled
            if (Schema::hasColumn('astrologers', 'po_at_5_enabled') && !Schema::hasColumn('astrologers', 'po5_enabled')) {
                $table->renameColumn('po_at_5_enabled', 'po5_enabled');
            }

            // Reverse: po_at_5_rate_per_minute to po5_user_rate
            if (Schema::hasColumn('astrologers', 'po_at_5_rate_per_minute') && !Schema::hasColumn('astrologers', 'po5_user_rate')) {
                $table->renameColumn('po_at_5_rate_per_minute', 'po5_user_rate');
            }

            // Reverse: po_at_5_astrologer_rate to po5_astrologer_rate
            if (Schema::hasColumn('astrologers', 'po_at_5_astrologer_rate') && !Schema::hasColumn('astrologers', 'po5_astrologer_rate')) {
                $table->renameColumn('po_at_5_astrologer_rate', 'po5_astrologer_rate');
            }

            // Reverse: po_at_5_sessions to po5_sessions
            if (Schema::hasColumn('astrologers', 'po_at_5_sessions') && !Schema::hasColumn('astrologers', 'po5_sessions')) {
                $table->renameColumn('po_at_5_sessions', 'po5_sessions');
            }
        });
    }
};
