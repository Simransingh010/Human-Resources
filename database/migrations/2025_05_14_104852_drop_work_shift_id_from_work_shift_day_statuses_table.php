<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_shift_day_statuses', function (Blueprint $table) {
            // If the work_shift_id column exists, drop its constraints and column
            if (Schema::hasColumn('work_shift_day_statuses', 'work_shift_id')) {
                $table->dropUnique('unique_day_status_per_shift');
                $table->dropForeign(['work_shift_id']);
                $table->dropColumn('work_shift_id');
            }

            // Create unique key on firm_id and day_status_code
            $table->unique(['firm_id', 'day_status_code'], 'unique_day_status_per_shift');
        });
    }

    public function down()
    {
        Schema::table('work_shift_day_statuses', function (Blueprint $table) {
            // Drop the unique key on firm_id and day_status_code
            $table->dropUnique('unique_day_status_per_shift');

            // Re-add the work_shift_id column
            $table->unsignedBigInteger('work_shift_id')->after('firm_id');
            // Recreate foreign key
            $table->foreign('work_shift_id')
                ->references('id')
                ->on('work_shifts')
                ->onDelete('cascade');
            // Recreate original unique key on work_shift_id and day_status_code
            $table->unique(['work_shift_id', 'day_status_code'], 'unique_day_status_per_shift');
        });
    }
};
