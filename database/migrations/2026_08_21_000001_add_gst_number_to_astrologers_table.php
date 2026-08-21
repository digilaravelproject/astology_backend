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
        Schema::table('astrologers', function (Blueprint $table) {
            if (!Schema::hasColumn('astrologers', 'gst_number')) {
                $table->string('gst_number', 15)->nullable()->after('id_proof_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('astrologers', function (Blueprint $table) {
            if (Schema::hasColumn('astrologers', 'gst_number')) {
                $table->dropColumn('gst_number');
            }
        });
    }
};
