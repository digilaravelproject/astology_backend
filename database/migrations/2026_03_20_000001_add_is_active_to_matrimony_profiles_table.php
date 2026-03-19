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
        if (!Schema::hasTable('matrimony_profiles')) {
            return;
        }

        Schema::table('matrimony_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('matrimony_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('profile_photo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('matrimony_profiles')) {
            return;
        }

        Schema::table('matrimony_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('matrimony_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
