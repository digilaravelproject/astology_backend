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
        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('id')->constrained('languages')->nullOnDelete();
        });

        Schema::table('remedies', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('id')->constrained('languages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropForeign(['blogs_language_id_foreign']);
            $table->dropColumn('language_id');
        });

        Schema::table('remedies', function (Blueprint $table) {
            $table->dropForeign(['remedies_language_id_foreign']);
            $table->dropColumn('language_id');
        });
    }
};
