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
        if (!Schema::hasTable('admin_fcm_settings')) {
            Schema::create('admin_fcm_settings', function (Blueprint $table) {
                $table->id();
                $table->string('project_id')->nullable();
                $table->string('service_account_json_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('default_sound')->default('default');
                $table->string('call_channel_id')->default('call_channel');
                $table->string('chat_channel_id')->default('chat_channel');
                $table->string('default_channel_id')->default('astology_notifications');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_fcm_settings');
    }
};
