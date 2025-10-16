<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->unsignedBigInteger('firm_id'); // Foreign key to firms table
            $table->unsignedBigInteger('employee_id')->nullable(); // Foreign key to employees table, nullable
            $table->string('camshot'); // Camshot policy
            $table->string('geo'); // Geo policy (new field)
            $table->string('manual_marking'); // Manual marking policy
            $table->json('geo_validation')->nullable(); // Geo validation rules (lat, long, distance)
            $table->json('ip_validation')->nullable(); // IP validation rules (from, to)
            $table->integer('back_date_max_minutes')->nullable(); // Max minutes for backdated attendance
            $table->integer('max_punches_a_day')->nullable(); // Max punches per day
            $table->json('grace_period_minutes')->nullable(); // Grace period rules
            $table->json('mark_absent_rule')->nullable(); // Rules for marking absent
            $table->json('overtime_rule')->nullable(); // Overtime rules
            $table->json('custom_rules')->nullable(); // Custom rules
            $table->date('valid_from')->nullable(); // Policy validity start date
            $table->date('valid_to')->nullable(); // Policy validity end date
            $table->text('policy_text')->nullable(); // Additional policy description

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_policies');
    }
};
