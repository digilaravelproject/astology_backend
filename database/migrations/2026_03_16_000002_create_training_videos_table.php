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
        Schema::create('training_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert sample training videos (used by the app training screen)
        \DB::table('training_videos')->insert([
            [
                'title' => 'How to use Google Translator in chat',
                'type' => 'Call/Chat',
                'description' => 'Step-by-step guide to using the Google Translator feature in chat.',
                'video_url' => 'https://example.com/videos/translate-chat.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/translate-chat.jpg',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'How to see your followers',
                'type' => 'Call/Chat',
                'description' => 'Learn where to find your followers and manage them.',
                'video_url' => 'https://example.com/videos/followers.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/followers.jpg',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'How to check your earnings today',
                'type' => 'Performance',
                'description' => 'A quick walkthrough of the earnings dashboard.',
                'video_url' => 'https://example.com/videos/earnings.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/earnings.jpg',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'How to update your profile bio',
                'type' => 'Profile',
                'description' => 'Tips for keeping your profile and bio up to date.',
                'video_url' => 'https://example.com/videos/update-bio.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/update-bio.jpg',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'How to manage your availability',
                'type' => 'Call/Chat',
                'description' => 'Set your availability for chat, call and video sessions.',
                'video_url' => 'https://example.com/videos/availability.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/availability.jpg',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'How to use the matching feature',
                'type' => 'Call/Chat',
                'description' => 'Learn how to use matching to get more requests.',
                'video_url' => 'https://example.com/videos/matching.mp4',
                'thumbnail_url' => 'https://example.com/thumbnails/matching.jpg',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_videos');
    }
};
