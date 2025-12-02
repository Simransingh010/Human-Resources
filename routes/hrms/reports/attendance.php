<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/reports/attendance-reports')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/attendance-register', App\Livewire\Hrms\Reports\AttendanceReports\AttendanceRegister::class)->name('hrms.reports.attendance-reports.attendance-register');
    Route::get('/attendance-summary', App\Livewire\Hrms\Reports\AttendanceReports\AttendanceSummary::class)->name('hrms.reports.attendance-reports.attendance-summary');
    Route::get('/week-off-summary', App\Livewire\Hrms\Reports\AttendanceReports\WeekOffSummary::class)->name('hrms.reports.attendance-reports.week-off-summary');
    Route::get('/detailed-week-off-report', App\Livewire\Hrms\Reports\AttendanceReports\DetailedWeekOffReport::class)->name('hrms.reports.attendance-reports.detailed-week-off-report');
    Route::get('/leave-summary-report', App\Livewire\Hrms\Reports\AttendanceReports\LeaveSummaryReport::class)->name('hrms.reports.attendance-reports.leave-summary-report');
    Route::get('/eu-attendance-report', App\Livewire\Hrms\Reports\AttendanceReports\EuAttendanceReport::class)->name('hrms.reports.attendance-reports.eu-attendance-report');
});
