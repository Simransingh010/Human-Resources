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
        Schema::table('emp_punches', function (Blueprint $table) {
            $table->unsignedBigInteger('emp_attendance_id')->after('employee_id');
            $table->foreign('emp_attendance_id')->references('id')->on('emp_attendances')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_punches', function (Blueprint $table) {
            $table->dropForeign(['emp_attendance_id']);
        });
    }
};
