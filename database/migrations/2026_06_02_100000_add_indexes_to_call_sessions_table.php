<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add database indexes to call_sessions to prevent full table scans.
 *
 * Without these indexes, every busy-status check, history query, and
 * billing-tick lookup does a complete table scan as data grows.
 *
 * Key queries covered:
 *   - WHERE provider_id = ? AND status IN (...)   → busy check on accept/initiate
 *   - WHERE consumer_id = ? AND status IN (...)   → consumer busy/pending check
 *   - WHERE consumer_id = ? ORDER BY created_at   → user call history
 *   - WHERE provider_id = ? ORDER BY created_at   → astrologer call history
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            // Single-column indexes
            $table->index('consumer_id', 'call_sessions_consumer_id_idx');
            $table->index('provider_id', 'call_sessions_provider_id_idx');
            $table->index('status',      'call_sessions_status_idx');
            $table->index('created_at',  'call_sessions_created_at_idx');

            // Composite indexes for the most common multi-column WHERE clauses
            // Used in busy-status checks (both initiateCall and acceptCall)
            $table->index(['provider_id', 'status'], 'call_sessions_provider_status_idx');
            $table->index(['consumer_id', 'status'], 'call_sessions_consumer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            $table->dropIndex('call_sessions_consumer_id_idx');
            $table->dropIndex('call_sessions_provider_id_idx');
            $table->dropIndex('call_sessions_status_idx');
            $table->dropIndex('call_sessions_created_at_idx');
            $table->dropIndex('call_sessions_provider_status_idx');
            $table->dropIndex('call_sessions_consumer_status_idx');
        });
    }
};
