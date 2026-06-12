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
        Schema::table('call_sessions', function (Blueprint $table) {
            $table->string('call_type', 16)->default('audio')->after('provider_id');
            $table->longText('consumer_sdp')->nullable()->after('last_billed_at');
            $table->longText('provider_sdp')->nullable()->after('consumer_sdp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            $table->dropColumn(['call_type', 'consumer_sdp', 'provider_sdp']);
        });
    }
};
