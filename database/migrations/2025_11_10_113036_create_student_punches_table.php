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
        Schema::create('student_punches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_centre_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('student_attendance_id')->nullable();
            $table->date('date');
            $table->dateTime('punch_datetime');
            $table->unsignedBigInteger('attendance_location_id')->nullable();
            $table->json('punch_geolocation')->nullable();
            $table->string('in_out')->nullable();
            $table->string('punch_type')->nullable();
            $table->string('device_id')->nullable();
            $table->json('punch_details')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('study_centre_id')->references('id')->on('study_centres')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('student_attendance_id')->references('id')->on('student_attendances')->onDelete('set null');
            $table->foreign('attendance_location_id')->references('id')->on('attend_locations')->onDelete('set null');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_punches');
    }
};
