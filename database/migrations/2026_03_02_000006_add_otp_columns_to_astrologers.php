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
        if (!Schema::hasTable('astrologers')) {
            return;
        }

        Schema::table('astrologers', function (Blueprint $table) {
            if (!Schema::hasColumn('astrologers', 'otp')) {
                $table->string('otp', 10)->nullable()->after('status');
            }

            if (!Schema::hasColumn('astrologers', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp');
            }

            if (!Schema::hasColumn('astrologers', 'otp_verified_at')) {
                $table->timestamp('otp_verified_at')->nullable()->after('otp_expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('astrologers')) {
            return;
        }

        Schema::table('astrologers', function (Blueprint $table) {
            if (Schema::hasColumn('astrologers', 'otp')) {
                $table->dropColumn('otp');
            }

            if (Schema::hasColumn('astrologers', 'otp_expires_at')) {
                $table->dropColumn('otp_expires_at');
            }

            if (Schema::hasColumn('astrologers', 'otp_verified_at')) {
                $table->dropColumn('otp_verified_at');
            }
        });
    }
};
