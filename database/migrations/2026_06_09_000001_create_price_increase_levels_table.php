<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_increase_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level_number')->unique();
            $table->integer('required_busy_minutes');
            $table->decimal('max_increase_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_increase_levels');
    }
};
