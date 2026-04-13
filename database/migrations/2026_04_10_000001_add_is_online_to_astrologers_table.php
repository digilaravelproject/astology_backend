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
            if (!Schema::hasColumn('astrologers', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('status')->comment('Online status of astrologer (0=offline, 1=online)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('astrologers', function (Blueprint $table) {
            if (Schema::hasColumn('astrologers', 'is_online')) {
                $table->dropColumn('is_online');
            }
        });
    }
};
