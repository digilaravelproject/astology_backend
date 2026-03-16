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
        Schema::table('astrologer_communities', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('is_liked');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->text('report_reason')->nullable()->after('blocked_at');
            $table->timestamp('reported_at')->nullable()->after('report_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('astrologer_communities', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'blocked_at', 'report_reason', 'reported_at']);
        });
    }
};
