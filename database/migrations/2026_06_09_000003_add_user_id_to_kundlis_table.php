<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kundlis', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
        });

        $firstUserId = DB::table('users')->value('id');
        if ($firstUserId) {
            DB::table('kundlis')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }

        Schema::table('kundlis', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kundlis', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
