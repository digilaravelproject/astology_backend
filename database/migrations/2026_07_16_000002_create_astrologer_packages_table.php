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
        Schema::create('astrologer_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('astrologer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->integer('duration'); // in seconds
            $table->decimal('commission_percentage', 5, 2)->nullable(); // Overrides global settings if not null
            $table->timestamps();

            $table->index('astrologer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astrologer_packages');
    }
};
