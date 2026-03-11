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
        Schema::table('astrologer_other_details', function (Blueprint $table) {
            $table->dropColumn(['daily_contribution_hours', 'heard_about']);
            $table->text('bio')->nullable()->after('current_address');
            $table->date('date_of_birth')->nullable()->after('bio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('astrologer_other_details', function (Blueprint $table) {
            $table->integer('daily_contribution_hours')->nullable()->after('current_address');
            $table->string('heard_about')->nullable()->after('daily_contribution_hours');
            $table->dropColumn(['bio', 'date_of_birth']);
        });
    }
};
