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
        Schema::table('users', function (Blueprint $table) {
            // Safety check: Only add the columns if they do not exist
            if (!Schema::hasColumn('users', 'is_online')) {
                $table->boolean('is_online')->default(false);
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_busy')) {
                $table->boolean('is_busy')->default(false);
            }
            if (!Schema::hasColumn('users', 'busy_session_id')) {
                $table->unsignedBigInteger('busy_session_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('users', 'is_online')) $columnsToDrop[] = 'is_online';
            if (Schema::hasColumn('users', 'last_seen_at')) $columnsToDrop[] = 'last_seen_at';
            if (Schema::hasColumn('users', 'is_busy')) $columnsToDrop[] = 'is_busy';
            if (Schema::hasColumn('users', 'busy_session_id')) $columnsToDrop[] = 'busy_session_id';
            if (Schema::hasColumn('users', 'fcm_token')) $columnsToDrop[] = 'fcm_token';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
