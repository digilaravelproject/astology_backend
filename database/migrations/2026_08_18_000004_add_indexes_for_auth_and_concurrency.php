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
        // 1. Indexes on `users` table for lightning-fast auth & role lookups
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->index('phone', 'users_phone_index');
            }
            if (Schema::hasColumn('users', 'user_type')) {
                $table->index('user_type', 'users_user_type_index');
            }
            if (Schema::hasColumns('users', ['phone', 'user_type'])) {
                $table->index(['phone', 'user_type'], 'users_phone_user_type_index');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->index('deleted_at', 'users_deleted_at_index');
            }
        });

        // 2. Indexes on `astrologers` table
        Schema::table('astrologers', function (Blueprint $table) {
            if (Schema::hasColumn('astrologers', 'status')) {
                $table->index('status', 'astrologers_status_index');
            }
            if (Schema::hasColumn('astrologers', 'is_online')) {
                $table->index('is_online', 'astrologers_is_online_index');
            }
            if (Schema::hasColumns('astrologers', ['user_id', 'status'])) {
                $table->index(['user_id', 'status'], 'astrologers_user_id_status_index');
            }
        });

        // 3. Composite indexes on `wallets` & `wallet_transactions`
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (Schema::hasColumns('wallets', ['user_id', 'balance'])) {
                    $table->index(['user_id', 'balance'], 'wallets_user_id_balance_index');
                }
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (Schema::hasColumns('wallet_transactions', ['wallet_id', 'created_at'])) {
                    $table->index(['wallet_id', 'created_at'], 'wallet_transactions_wallet_id_created_at_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_phone_index');
            $table->dropIndex('users_user_type_index');
            $table->dropIndex('users_phone_user_type_index');
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropIndex('users_deleted_at_index');
            }
        });

        Schema::table('astrologers', function (Blueprint $table) {
            $table->dropIndex('astrologers_status_index');
            $table->dropIndex('astrologers_is_online_index');
            $table->dropIndex('astrologers_user_id_status_index');
        });

        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropIndex('wallets_user_id_balance_index');
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex('wallet_transactions_wallet_id_created_at_index');
            });
        }
    }
};
