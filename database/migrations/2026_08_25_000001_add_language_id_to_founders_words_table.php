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
        Schema::table('founders_words', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('id')->constrained('languages')->nullOnDelete();
            $table->index(['is_active', 'language_id', 'created_at'], 'idx_founders_words_active_lang_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('founders_words', function (Blueprint $table) {
            $table->dropIndex('idx_founders_words_active_lang_created');
            $table->dropForeign(['founders_words_language_id_foreign']);
            $table->dropColumn('language_id');
        });
    }
};
