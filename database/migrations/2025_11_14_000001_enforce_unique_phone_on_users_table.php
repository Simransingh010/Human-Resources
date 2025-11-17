<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !$this->indexExists('users', 'users_phone_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }

        if (Schema::hasTable('students') && !$this->indexExists('students', 'students_user_id_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('user_id', 'students_user_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && $this->indexExists('users', 'users_phone_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_phone_unique');
            });
        }

        if (Schema::hasTable('students') && $this->indexExists('students', 'students_user_id_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('students_user_id_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $tableName = $connection->getTablePrefix() . $table;

        $result = DB::select(
            'SELECT COUNT(1) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $tableName, $index]
        );

        return !empty($result) && (int) ($result[0]->aggregate ?? 0) > 0;
    }
};

