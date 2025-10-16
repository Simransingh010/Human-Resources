<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_shift_days', function (Blueprint $table) {
            // Drop old column
            if (Schema::hasColumn('work_shift_days', 'day_status')) {
                $table->dropColumn('day_status');
            }

            // Add new column
            $table->foreignId('work_shift_day_status_id')
                ->after('end_time')
                ->nullable()
                ->constrained()
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_shift_days', function (Blueprint $table) {
            $table->string('day_status')->nullable(); // Restore if needed
            $table->dropConstrainedForeignId('work_shift_day_status_id');
        });
    }
};
