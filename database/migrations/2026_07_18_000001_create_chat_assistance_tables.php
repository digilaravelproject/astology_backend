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
        Schema::create('chat_assistance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['consumer_id', 'provider_id']);
        });

        Schema::create('chat_assistance_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_assistance_session_id')->constrained('chat_assistance_sessions')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->string('attachment_url')->nullable();
            $table->enum('type', ['text', 'image'])->default('text');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_delivered')->default(false);
            $table->foreignId('call_session_id')->nullable()->constrained('call_sessions')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('chat_assistance_astrologer_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('astrologer_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->integer('reply_count')->default(0);
            $table->timestamps();

            $table->unique(['astrologer_id', 'date']);
        });

        Schema::create('chat_assistance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_assistance_session_id')->constrained('chat_assistance_sessions')->onDelete('cascade');
            $table->string('event_name');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_assistance_events');
        Schema::dropIfExists('chat_assistance_astrologer_limits');
        Schema::dropIfExists('chat_assistance_messages');
        Schema::dropIfExists('chat_assistance_sessions');
    }
};
