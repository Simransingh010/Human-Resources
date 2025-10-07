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
        Schema::create('emp_prev_salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('emp_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->string('salary_period_from')->nullable();
            $table->string('salary_period_to')->nullable();
            $table->unsignedBigInteger('emp_prev_employement_records_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_prev_salary_components');
    }
};
