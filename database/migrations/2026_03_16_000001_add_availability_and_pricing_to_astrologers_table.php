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
        if (!Schema::hasTable('astrologers')) {
            return;
        }

        Schema::table('astrologers', function (Blueprint $table) {
            if (!Schema::hasColumn('astrologers', 'chat_enabled')) {
                $table->boolean('chat_enabled')->default(false)->after('status');
            }

            if (!Schema::hasColumn('astrologers', 'call_enabled')) {
                $table->boolean('call_enabled')->default(false)->after('chat_enabled');
            }

            if (!Schema::hasColumn('astrologers', 'video_call_enabled')) {
                $table->boolean('video_call_enabled')->default(false)->after('call_enabled');
            }

            if (!Schema::hasColumn('astrologers', 'chat_rate_per_minute')) {
                $table->decimal('chat_rate_per_minute', 8, 2)->default(15.00)->after('video_call_enabled');
            }

            if (!Schema::hasColumn('astrologers', 'call_rate_per_minute')) {
                $table->decimal('call_rate_per_minute', 8, 2)->default(15.00)->after('chat_rate_per_minute');
            }

            if (!Schema::hasColumn('astrologers', 'video_call_rate_per_minute')) {
                $table->decimal('video_call_rate_per_minute', 8, 2)->default(15.00)->after('call_rate_per_minute');
            }

            if (!Schema::hasColumn('astrologers', 'po_at_5_enabled')) {
                $table->boolean('po_at_5_enabled')->default(false)->after('video_call_rate_per_minute');
            }

            if (!Schema::hasColumn('astrologers', 'po_at_5_rate_per_minute')) {
                $table->decimal('po_at_5_rate_per_minute', 8, 2)->default(5.00)->after('po_at_5_enabled');
            }

            if (!Schema::hasColumn('astrologers', 'po_at_5_sessions')) {
                $table->unsignedTinyInteger('po_at_5_sessions')->default(5)->after('po_at_5_rate_per_minute');
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
            if (Schema::hasColumn('astrologers', 'po_at_5_sessions')) {
                $table->dropColumn('po_at_5_sessions');
            }
            if (Schema::hasColumn('astrologers', 'po_at_5_rate_per_minute')) {
                $table->dropColumn('po_at_5_rate_per_minute');
            }
            if (Schema::hasColumn('astrologers', 'po_at_5_enabled')) {
                $table->dropColumn('po_at_5_enabled');
            }
            if (Schema::hasColumn('astrologers', 'video_call_rate_per_minute')) {
                $table->dropColumn('video_call_rate_per_minute');
            }
            if (Schema::hasColumn('astrologers', 'call_rate_per_minute')) {
                $table->dropColumn('call_rate_per_minute');
            }
            if (Schema::hasColumn('astrologers', 'chat_rate_per_minute')) {
                $table->dropColumn('chat_rate_per_minute');
            }
            if (Schema::hasColumn('astrologers', 'video_call_enabled')) {
                $table->dropColumn('video_call_enabled');
            }
            if (Schema::hasColumn('astrologers', 'call_enabled')) {
                $table->dropColumn('call_enabled');
            }
            if (Schema::hasColumn('astrologers', 'chat_enabled')) {
                $table->dropColumn('chat_enabled');
            }
        });
    }
};
