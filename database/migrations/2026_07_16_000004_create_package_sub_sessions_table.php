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
        Schema::create('package_sub_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_purchase_id')->constrained('package_purchases')->onDelete('cascade');
            $table->enum('mode', ['chat', 'call']);
            $table->unsignedBigInteger('chat_session_id')->nullable()->index(); // linked ChatSession
            $table->unsignedBigInteger('call_session_id')->nullable()->index(); // linked CallSession
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_used')->default(0); // in seconds
            $table->timestamps();

            $table->index('package_purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_sub_sessions');
    }
};
