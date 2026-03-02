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
        // Add user_type to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->default('user')->after('password');
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
            $table->string('country')->nullable()->after('city');
        });

        // Create astrologers table for astrologer-specific data
        Schema::create('astrologers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('years_of_experience')->nullable();
            $table->json('areas_of_expertise')->nullable();
            $table->json('languages')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('bio')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('certificate')->nullable();
            $table->string('id_proof_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astrologers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'phone', 'city', 'country']);
        });
    }
};
