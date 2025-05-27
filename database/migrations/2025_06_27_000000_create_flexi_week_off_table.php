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
        Schema::create('flexi_week_off', function (Blueprint $table) {
            $table->id();
            $table->integer('firm_id');
            $table->integer('employee_id');
            $table->string('attendance_status_main')->nullable();
            $table->unsignedBigInteger('availed_emp_attendance_id')->nullable();
            $table->unsignedBigInteger('consumed_emp_attendance_id')->nullable();
            $table->string('week_off_Status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flexi_week_off');
    }
}; 