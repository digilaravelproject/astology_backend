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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_transactions', 'base_amount')) {
                $table->decimal('base_amount', 14, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->nullable()->after('base_amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'gst_amount')) {
                $table->decimal('gst_amount', 14, 2)->nullable()->after('gst_percent');
            }
            if (!Schema::hasColumn('wallet_transactions', 'total_amount')) {
                $table->decimal('total_amount', 14, 2)->nullable()->after('gst_amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('total_amount')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $columns = ['invoice_number', 'total_amount', 'gst_amount', 'gst_percent', 'base_amount'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('wallet_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
