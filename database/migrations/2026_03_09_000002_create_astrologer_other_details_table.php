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
        Schema::create('astrologer_other_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('astrologer_id')->constrained('astrologers')->onDelete('cascade');
            $table->string('gender')->nullable();
            $table->text('current_address')->nullable();
            $table->integer('daily_contribution_hours')->nullable();
            $table->string('heard_about')->nullable();
            $table->string('website_link')->nullable();
            $table->string('instagram_username')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astrologer_other_details');
    }
};
