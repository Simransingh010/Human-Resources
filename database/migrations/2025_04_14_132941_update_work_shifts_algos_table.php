<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_shifts_algos', function (Blueprint $table) {
            // Add missing columns
            $table->date('start_date')->after('work_shift_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->after('end_date');
            $table->time('end_time')->after('start_time');
            $table->text('work_breaks')->nullable()->after('week_off_pattern');
            $table->text('late_panelty')->nullable()->after('rules_config');
            $table->text('comp_off')->nullable()->after('late_panelty');
            $table->longText('week_off_pattern')->change();


            if (Schema::hasColumn('work_shifts_algos', 'is_active')) {
                $table->renameColumn('is_active', 'is_inactive');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_shifts_algos', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'work_breaks',
                'late_panelty',
                'comp_off'
            ]);


            if (Schema::hasColumn('work_shifts_algos', 'is_inactive')) {
                $table->renameColumn('is_inactive', 'is_active');
            }
        });
    }
};
