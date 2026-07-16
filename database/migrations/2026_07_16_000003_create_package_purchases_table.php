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
        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('astrologer_id')->constrained('users')->onDelete('cascade');
            $table->integer('total_duration'); // total seconds purchased
            $table->integer('remaining_duration'); // remaining seconds left
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('commission_percentage', 5, 2); // Split percentage for astrologer
            $table->decimal('admin_earnings', 10, 2);
            $table->decimal('astrologer_earnings', 10, 2);
            $table->enum('status', ['active', 'exhausted'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['astrologer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_purchases');
    }
};
