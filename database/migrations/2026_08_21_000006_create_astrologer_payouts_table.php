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
        Schema::create('astrologer_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number', 50)->unique();
            $table->unsignedBigInteger('astrologer_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->index();
            
            // Financials & Tax Breakdown
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('tds_percent', 5, 2)->default(0.00);
            $table->decimal('tds_amount', 12, 2)->default(0.00);
            $table->decimal('net_paid_amount', 12, 2);
            
            // Payment Disbursement Information
            $table->string('payment_mode', 50)->default('Bank Transfer');
            $table->string('utr_number', 100)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->json('bank_details_snapshot')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->string('receipt_proof', 255)->nullable();
            
            // Governance & Audit
            $table->string('status', 20)->default('completed');
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            
            $table->timestamps();
            
            $table->foreign('astrologer_id')->references('id')->on('astrologers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astrologer_payouts');
    }
};
