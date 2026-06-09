<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kundlis', 'user_id')) {
            DB::statement('ALTER TABLE kundlis ADD COLUMN user_id BIGINT UNSIGNED NULL');
        }

        $firstUserId = DB::table('users')->value('id');
        if ($firstUserId) {
            DB::table('kundlis')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }

        $fkExists = DB::selectOne("
            SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'kundlis'
              AND COLUMN_NAME = 'user_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!$fkExists) {
            DB::statement('ALTER TABLE kundlis MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE kundlis ADD CONSTRAINT kundlis_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kundlis', 'user_id')) {
            DB::statement('ALTER TABLE kundlis DROP FOREIGN KEY kundlis_user_id_foreign');
            DB::statement('ALTER TABLE kundlis DROP COLUMN user_id');
        }
    }
};
