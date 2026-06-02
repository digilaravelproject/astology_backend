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
        Schema::table('users', function (Blueprint $table) {
            $table->string('relationship_status')->nullable()->after('place_of_birth');
            $table->string('occupation')->nullable()->after('relationship_status');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->text('question')->nullable()->after('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['relationship_status', 'occupation']);
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['question']);
        });
    }
};
