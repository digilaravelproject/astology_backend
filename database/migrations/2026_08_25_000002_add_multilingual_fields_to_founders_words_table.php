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
            $table->string('title_en')->nullable()->after('message');
            $table->text('message_en')->nullable()->after('title_en');
            $table->string('title_hi')->nullable()->after('message_en');
            $table->text('message_hi')->nullable()->after('title_hi');
            $table->string('title_mr')->nullable()->after('message_hi');
            $table->text('message_mr')->nullable()->after('title_mr');
            $table->json('translations')->nullable()->after('message_mr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('founders_words', function (Blueprint $table) {
            $table->dropColumn([
                'title_en',
                'message_en',
                'title_hi',
                'message_hi',
                'title_mr',
                'message_mr',
                'translations',
            ]);
        });
    }
};
