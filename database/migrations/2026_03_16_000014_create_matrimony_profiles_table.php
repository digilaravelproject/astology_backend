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
        Schema::create('matrimony_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('created_for')->nullable(); // Myself / My Son / ...
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('height')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('location')->nullable();
            $table->string('education')->nullable();
            $table->string('job_title')->nullable();
            $table->string('annual_income')->nullable();
            $table->text('about')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();

            $table->index(['location', 'education', 'job_title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matrimony_profiles');
    }
};
