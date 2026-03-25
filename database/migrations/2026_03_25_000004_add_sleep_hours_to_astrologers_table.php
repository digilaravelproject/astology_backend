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
        Schema::table('astrologers', function (Blueprint $table) {
            $table->time('sleep_start_time')->nullable()->after('availability');
            $table->time('sleep_end_time')->nullable()->after('sleep_start_time');
            $table->integer('sleep_duration_minutes')->nullable()->after('sleep_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('astrologers', function (Blueprint $table) {
            $table->dropColumn(['sleep_start_time', 'sleep_end_time', 'sleep_duration_minutes']);
        });
    }
};
