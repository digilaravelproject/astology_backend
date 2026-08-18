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
        if (!Schema::hasTable('broadcast_notifications')) {
            Schema::create('broadcast_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body');
                $table->string('image_url')->nullable();
                $table->enum('target_type', ['all', 'users', 'astrologers', 'single_user'])->default('all');
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->string('click_action')->nullable();
                $table->json('data_payload')->nullable();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('successful_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_notifications');
    }
};
