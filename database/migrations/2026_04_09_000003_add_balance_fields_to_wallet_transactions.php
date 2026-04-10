<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_transactions', 'balance_before')) {
                $table->decimal('balance_before', 14, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $table->decimal('balance_after', 14, 2)->nullable()->after('balance_before');
            }
            if (!Schema::hasColumn('wallet_transactions', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('balance_after');
            }
            if (!Schema::hasColumn('wallet_transactions', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_transactions', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('wallet_transactions', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $table->dropColumn('balance_after');
            }
            if (Schema::hasColumn('wallet_transactions', 'balance_before')) {
                $table->dropColumn('balance_before');
            }
        });
    }
};
