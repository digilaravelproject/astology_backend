<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change status from enum('active','pending') to VARCHAR(20) to support 'approved', 'active', 'pending', 'rejected'
        DB::statement("ALTER TABLE `astrologer_galleries` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");

        // 2. Normalize existing 'active' records to 'approved'
        DB::statement("UPDATE `astrologer_galleries` SET `status` = 'approved' WHERE `status` = 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE `astrologer_galleries` SET `status` = 'active' WHERE `status` = 'approved'");
        DB::statement("ALTER TABLE `astrologer_galleries` MODIFY COLUMN `status` ENUM('active', 'pending') NOT NULL DEFAULT 'pending'");
    }
};
