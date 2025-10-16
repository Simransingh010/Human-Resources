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
        Schema::create('emp_attendance_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('attendance_status_code');
            $table->string('attendance_status_label');
            $table->text('attendance_status_desc')->nullable();
            $table->string('paid_percent');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreignId('work_shift_id')->constrained('work_shifts')->onDelete('cascade');

        });
        Schema::create('attend_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();;
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');

        });


        Schema::table('emp_punches', function (Blueprint $table) {
            $table->unsignedBigInteger('attend_location_id')->after('punch_type')->nullable();
            $table->foreign('attend_location_id')->references('id')->on('attend_locations')->onDelete('cascade');
        });

        Schema::table('emp_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('attend_location_id')->after('final_day_weightage')->nullable();
            $table->foreign('attend_location_id')->references('id')->on('attend_locations')->onDelete('cascade');
            $table->unsignedBigInteger('emp_attendance_status_id')->after('attend_location_id')->nullable();
            $table->foreign('emp_attendance_status_id')->references('id')->on('emp_attendance_statuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('emp_punches', function (Blueprint $table) {
            $table->dropColumn('attend_location_id');
            $table->dropForeign(['attend_location_id']);
        });
        Schema::table('emp_attendances', function (Blueprint $table) {
            $table->dropColumn('attend_location_id');
            $table->dropForeign(['attend_location_id']);
            $table->dropColumn('emp_attendance_status_id');
            $table->dropForeign(['emp_attendance_status_id']);
        });
        Schema::dropIfExists('attend_locations');
        Schema::dropIfExists('emp_attendance_statuses');
    }
};
