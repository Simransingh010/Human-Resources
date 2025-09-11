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
        Schema::table('work_shift_days', function (Blueprint $table) {
            if (!Schema::hasColumn('work_shift_days', 'is_overnight')) {
                $table->boolean('is_overnight')->default(0)->after('day_status_main');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_shift_days', function (Blueprint $table) {
            if (Schema::hasColumn('work_shift_days', 'is_overnight')) {
                $table->dropColumn('is_overnight');
            }
        });
    }
};
