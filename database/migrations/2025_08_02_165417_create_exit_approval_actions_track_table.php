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
        Schema::create('exit_approval_actions_track', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('exit_approvals_steps_track_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('exit_approval_step_id');
            $table->string('clearance_type')->nullable();
            $table->string('clearance_item');
            $table->string('clearance_desc')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('clearance_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exit_approval_actions_track');
    }
};
