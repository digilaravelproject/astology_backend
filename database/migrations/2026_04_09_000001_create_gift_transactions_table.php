<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gift_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('astrologer_id');
            $table->unsignedBigInteger('gift_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_provider')->nullable();
            $table->string('provider_order_id')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('completed');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('astrologer_id')->references('id')->on('astrologers')->onDelete('cascade');
            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gift_transactions');
    }
};
