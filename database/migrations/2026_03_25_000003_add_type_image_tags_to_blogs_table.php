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
            $table->enum('type', ['article', 'news', 'update', 'education', 'announcement'])->default('article')->after('title');
            $table->string('blog_image')->nullable()->after('author');
            $table->json('blog_tags')->nullable()->after('blog_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['type', 'blog_image', 'blog_tags']);
        });
    }
};
