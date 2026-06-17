<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->boolean('is_camera_on')->default(false)->after('is_broadcasting');
            $table->boolean('is_audio_on')->default(false)->after('is_camera_on');
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_camera_on', 'is_audio_on']);
        });
    }
};
