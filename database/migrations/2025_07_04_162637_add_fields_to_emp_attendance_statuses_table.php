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
        Schema::table('emp_attendance_statuses', function (Blueprint $table) {
            //
            $table->string('attendance_status_main')->nullable()->after('paid_percent');
            $table->longText('attribute_json')->nullable()->after('attendance_status_main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_attendance_statuses', function (Blueprint $table) {
            //
            $table->dropColumn('attendance_status_main');
            $table->dropColumn('attribute_json');
        });
    }
};
