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
        Schema::create('exit_approvals_steps_track', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('exit_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('exit_employee_department_id');
            $table->unsignedBigInteger('exit_employee_designation_id');
            $table->integer('flow_order');
            $table->string('approval_type');
            $table->unsignedBigInteger('department_id');
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exit_approvals_steps_track');
    }
};
