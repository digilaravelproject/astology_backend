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
        Schema::table('messages', function (Blueprint $table) {
            // Index for fast session message querying and ordering
            $table->index(['chat_session_id', 'created_at'], 'idx_messages_session_created');
            $table->index(['chat_session_id', 'id'], 'idx_messages_session_id');
            // Index for fast unread count aggregation
            $table->index(['receiver_id', 'is_read'], 'idx_messages_receiver_read');
            // Index for direct participant lookup
            $table->index(['sender_id', 'receiver_id'], 'idx_messages_sender_receiver');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            // Composite index for fast active/historical session filtering
            $table->index(['consumer_id', 'status'], 'idx_chat_sessions_consumer_status');
            $table->index(['provider_id', 'status'], 'idx_chat_sessions_provider_status');
        });

        Schema::table('call_sessions', function (Blueprint $table) {
            // Composite index for fast call session filtering
            $table->index(['consumer_id', 'status'], 'idx_call_sessions_consumer_status');
            $table->index(['provider_id', 'status'], 'idx_call_sessions_provider_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_session_created');
            $table->dropIndex('idx_messages_session_id');
            $table->dropIndex('idx_messages_receiver_read');
            $table->dropIndex('idx_messages_sender_receiver');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_chat_sessions_consumer_status');
            $table->dropIndex('idx_chat_sessions_provider_status');
        });

        Schema::table('call_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_call_sessions_consumer_status');
            $table->dropIndex('idx_call_sessions_provider_status');
        });
    }
};
