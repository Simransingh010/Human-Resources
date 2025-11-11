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
        Schema::create('student_education_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('student_code')->nullable();
            $table->date('doh')->nullable();
            $table->unsignedBigInteger('study_centre_id')->nullable();
            $table->unsignedBigInteger('reporting_coach_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->date('doe')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('joblocations')->onDelete('set null');
            $table->foreign('reporting_coach_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_education_details');
    }
};
