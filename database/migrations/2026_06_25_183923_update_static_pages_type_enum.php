<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE static_pages MODIFY COLUMN type ENUM('faq', 'privacy_policy', 'terms_and_conditions', 'payment_policy', 'about_us', 'customer_support', 'contact_us') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE static_pages MODIFY COLUMN type ENUM('faq', 'privacy_policy', 'terms_and_conditions', 'payment_policy') NOT NULL");
    }
};
