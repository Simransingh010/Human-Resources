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
        Schema::table('emp_attendances', function (Blueprint $table) {
            $table->string('attendance_status_main',5)->after('attend_location_id')->nullable();
//            Present (P) – The employee has marked attendance for the day.
//            Absent (A) – The employee did not mark attendance for the day.
//            Half Day (HD) – The employee attended only for half of the working hours.
//            Leave (L) – The employee is on an approved leave.
//            Work from Remote (WFR) – The employee is working remotely.
//            On Duty (OD) – The employee is on an official visit or external duty.
//            Holiday (H) – A scheduled holiday (weekend, festival, or public holiday).
//            Weekend (W) – A non-working day as per the organization's calendar.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_attendances', function (Blueprint $table) {
            $table->dropColumn(['attendance_status_main']);
        });
    }
};
