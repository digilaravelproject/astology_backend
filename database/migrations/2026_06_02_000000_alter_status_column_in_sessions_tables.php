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
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->string('status', 50)->default('initiated')->change();
        });

        Schema::table('call_sessions', function (Blueprint $table) {
            $table->string('status', 50)->default('initiated')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->enum('status', ['initiated', 'accepted', 'ongoing', 'completed', 'rejected'])->default('initiated')->change();
        });

        Schema::table('call_sessions', function (Blueprint $table) {
            $table->enum('status', ['initiated', 'ringing', 'accepted', 'ongoing', 'completed', 'missed', 'rejected', 'failed'])->default('initiated')->change();
        });
    }
};
