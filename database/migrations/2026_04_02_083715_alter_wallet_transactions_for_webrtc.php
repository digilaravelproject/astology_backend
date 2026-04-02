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
            $table->decimal('balance_before', 14, 2)->nullable()->after('amount');
            $table->decimal('balance_after', 14, 2)->nullable()->after('balance_before');
            $table->string('reference_type')->nullable()->after('balance_after');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['balance_before', 'balance_after', 'reference_type', 'reference_id']);
        });
    }
};
