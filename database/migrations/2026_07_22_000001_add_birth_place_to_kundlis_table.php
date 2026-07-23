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
        Schema::table('kundlis', function (Blueprint $table) {
            $table->string('birth_place', 500)->nullable()->after('birth_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kundlis', function (Blueprint $table) {
            $table->dropColumn('birth_place');
        });
    }
};
