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
        if (!Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('fcm_token');
                $table->enum('device_type', ['android', 'ios', 'web'])->default('android');
                $table->string('device_id', 191)->nullable()->comment('Unique hardware UUID / client installation ID');
                $table->string('device_model', 100)->nullable()->comment('e.g. Pixel 8, iPhone 15 Pro');
                $table->string('app_version', 50)->nullable()->comment('App version');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'is_active']);
                $table->index(['device_id', 'user_id']);
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
