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
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'live_url',
                'stream_key',
                'stream_url',
                'started_at',
                'ended_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->string('live_url')->nullable()->after('session_type');
            $table->string('stream_key', 64)->nullable()->unique()->after('live_url');
            $table->string('stream_url', 255)->nullable()->after('stream_key');
            $table->timestamp('started_at')->nullable()->after('stream_url');
            $table->timestamp('ended_at')->nullable()->after('started_at');
        });
    }
};
