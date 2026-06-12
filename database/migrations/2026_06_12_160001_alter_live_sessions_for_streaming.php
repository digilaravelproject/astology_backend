<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->string('stream_key', 64)->nullable()->unique()->after('live_url');
            $table->string('stream_url', 255)->nullable()->after('stream_key');
            $table->timestamp('started_at')->nullable()->after('stream_url');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->unsignedInteger('viewer_count')->default(0)->after('current_participants');
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['stream_key', 'stream_url', 'started_at', 'ended_at', 'viewer_count']);
        });
    }
};
