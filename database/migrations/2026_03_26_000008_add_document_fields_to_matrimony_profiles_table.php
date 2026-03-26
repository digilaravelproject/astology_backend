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
        Schema::table('matrimony_profiles', function (Blueprint $table) {
            if (!Schema::hasColumns('matrimony_profiles', ['pan_card_number', 'driving_licence_number', 'aadhar_card_number'])) {
                $table->string('pan_card_number')->nullable()->after('profile_photo');
                $table->string('driving_licence_number')->nullable()->after('pan_card_number');
                $table->string('aadhar_card_number')->nullable()->after('driving_licence_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matrimony_profiles', function (Blueprint $table) {
            if (Schema::hasColumns('matrimony_profiles', ['pan_card_number', 'driving_licence_number', 'aadhar_card_number'])) {
                $table->dropColumn(['pan_card_number', 'driving_licence_number', 'aadhar_card_number']);
            }
        });
    }
};
