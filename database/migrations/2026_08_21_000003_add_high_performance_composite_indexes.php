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
        // 1. wallet_transactions composite indexes for ultra-fast ledger & summary queries
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                // For getWalletSummary & getEarningsHistory: sum('amount') where wallet_id + type + status + created_at
                $table->index(['wallet_id', 'transaction_type', 'status', 'created_at'], 'idx_wtx_wallet_type_status_created');
                // For transactions list excluding pending: where wallet_id + status
                $table->index(['wallet_id', 'status', 'created_at'], 'idx_wtx_wallet_status_created');
            });
        }

        // 2. astrologer_reviews composite indexes for review lookups & rating aggregation
        if (Schema::hasTable('astrologer_reviews')) {
            Schema::table('astrologer_reviews', function (Blueprint $table) {
                $table->index(['astrologer_id', 'rating'], 'idx_reviews_astrologer_rating');
                $table->index(['astrologer_id', 'created_at'], 'idx_reviews_astrologer_created');
                $table->index(['user_id', 'astrologer_id'], 'idx_reviews_user_astrologer');
            });
        }

        // 3. live_sessions composite indexes for active stream listing
        if (Schema::hasTable('live_sessions')) {
            Schema::table('live_sessions', function (Blueprint $table) {
                $table->index(['status', 'session_type', 'id'], 'idx_live_status_type_id');
                $table->index(['astrologer_id', 'status'], 'idx_live_astrologer_status');
            });
        }

        // 4. live_session_participants indexes
        if (Schema::hasTable('live_session_participants')) {
            Schema::table('live_session_participants', function (Blueprint $table) {
                $table->index(['live_session_id', 'user_id', 'left_at'], 'idx_lsp_session_user_left');
            });
        }

        // 5. live_comments index for stream message feed
        if (Schema::hasTable('live_comments')) {
            Schema::table('live_comments', function (Blueprint $table) {
                $table->index(['live_session_id', 'id'], 'idx_live_comments_session_id');
                $table->index(['live_session_id', 'created_at'], 'idx_live_comments_session_created');
            });
        }

        // 6. matrimony_profiles indexes for discovery & filtering
        if (Schema::hasTable('matrimony_profiles')) {
            Schema::table('matrimony_profiles', function (Blueprint $table) {
                $table->index(['is_active', 'gender', 'created_at'], 'idx_matrimony_active_gender_created');
                $table->index(['user_id', 'is_active'], 'idx_matrimony_user_active');
            });
        }

        // 7. app_notifications composite indexes for count & listing
        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->index(['user_id', 'is_read', 'created_at'], 'idx_notif_user_read_created');
            });
        }

        // 8. blogs composite index for active + language queries
        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->index(['is_active', 'language_id', 'created_at'], 'idx_blogs_active_lang_created');
            });
        }

        // 9. remedies composite index for active + language queries
        if (Schema::hasTable('remedies')) {
            Schema::table('remedies', function (Blueprint $table) {
                $table->index(['is_active', 'language_id', 'created_at'], 'idx_remedies_active_lang_created');
            });
        }

        // 10. kundlis composite index for user history
        if (Schema::hasTable('kundlis')) {
            Schema::table('kundlis', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_kundlis_user_created');
            });
        }

        // 11. astrologer_communities index for favorites & followers lookup
        if (Schema::hasTable('astrologer_communities')) {
            Schema::table('astrologer_communities', function (Blueprint $table) {
                $table->index(['user_id', 'is_liked'], 'idx_ac_user_liked');
                $table->index(['astrologer_id', 'is_liked'], 'idx_ac_astrologer_liked');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_wtx_wallet_type_status_created');
                $table->dropIndex('idx_wtx_wallet_status_created');
            });
        }

        if (Schema::hasTable('astrologer_reviews')) {
            Schema::table('astrologer_reviews', function (Blueprint $table) {
                $table->dropIndex('idx_reviews_astrologer_rating');
                $table->dropIndex('idx_reviews_astrologer_created');
                $table->dropIndex('idx_reviews_user_astrologer');
            });
        }

        if (Schema::hasTable('live_sessions')) {
            Schema::table('live_sessions', function (Blueprint $table) {
                $table->dropIndex('idx_live_status_type_id');
                $table->dropIndex('idx_live_astrologer_status');
            });
        }

        if (Schema::hasTable('live_session_participants')) {
            Schema::table('live_session_participants', function (Blueprint $table) {
                $table->dropIndex('idx_lsp_session_user_left');
            });
        }

        if (Schema::hasTable('live_comments')) {
            Schema::table('live_comments', function (Blueprint $table) {
                $table->dropIndex('idx_live_comments_session_id');
                $table->dropIndex('idx_live_comments_session_created');
            });
        }

        if (Schema::hasTable('matrimony_profiles')) {
            Schema::table('matrimony_profiles', function (Blueprint $table) {
                $table->dropIndex('idx_matrimony_active_gender_created');
                $table->dropIndex('idx_matrimony_user_active');
            });
        }

        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notif_user_read_created');
            });
        }

        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropIndex('idx_blogs_active_lang_created');
            });
        }

        if (Schema::hasTable('remedies')) {
            Schema::table('remedies', function (Blueprint $table) {
                $table->dropIndex('idx_remedies_active_lang_created');
            });
        }

        if (Schema::hasTable('kundlis')) {
            Schema::table('kundlis', function (Blueprint $table) {
                $table->dropIndex('idx_kundlis_user_created');
            });
        }

        if (Schema::hasTable('astrologer_communities')) {
            Schema::table('astrologer_communities', function (Blueprint $table) {
                $table->dropIndex('idx_ac_user_liked');
                $table->dropIndex('idx_ac_astrologer_liked');
            });
        }
    }
};
