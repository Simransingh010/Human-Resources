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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('study_centre_id')->nullable();
            $table->date('attendance_date');
            $table->string('attendance_status_main', 5)->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('study_centre_id')->references('id')->on('study_centres')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('joblocations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
