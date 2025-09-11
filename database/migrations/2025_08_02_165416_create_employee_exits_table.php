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
        Schema::create('employee_exits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('exit_type');
            $table->string('exit_reason');
            $table->unsignedBigInteger('initiated_by_user_id');
            $table->date('exit_request_date');
            $table->integer('notice_period_days')->default(0);
            $table->date('last_working_day')->nullable();
            $table->date('actual_relieving_date')->nullable();
            $table->string('status')->default('initiated');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_exits');
    }
};
