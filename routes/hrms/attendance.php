<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/attendance')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/attendance-policies', App\Livewire\Hrms\Attendance\AttendancePolicies::class)->name('attendance-policies.index');
    Route::get('/leave-types', App\Livewire\Hrms\Attendance\LeaveTypes::class)->name('leave-types.index');
    Route::get('/leaves-quota-template-setups', App\Livewire\Hrms\Attendance\LeavesQuotaTemplateSetups::class)->name('leaves-quota-template-setups.index');
    Route::get('/emp-leave-allocations', App\Livewire\Hrms\Attendance\EmpLeaveAllocations::class)->name('emp-leave-allocations.index');
    Route::get('/emp-leave-request-logs', App\Livewire\Hrms\Attendance\EmpLeaveRequestLogs::class)->name('emp-leave-request-logs.index');
    Route::get('/work-shift-days', App\Livewire\Hrms\Attendance\WorkShiftDays::class)->name('work-shift-days.index');
    Route::get('/work-shift-days-breaks', App\Livewire\Hrms\Attendance\WorkShiftDaysBreaks::class)->name('work-shift-days-breaks.index');
    Route::get('/work-shifts-algos', App\Livewire\Hrms\Attendance\WorkShiftsAlgos::class)->name('work-shifts-algos.index');
    Route::get('/emp-leave-requests', App\Livewire\Hrms\Attendance\EmpLeaveRequests::class)->name('employee-leave-requests.index');
    Route::get('/emp-attendances', App\Livewire\Hrms\Attendance\EmpAttendances::class)->name('hrms.attendance.emp-attendances');
    Route::get('/emp-work-shifts', App\Livewire\Hrms\Attendance\EmpWorkShifts::class)->name('emp-work-shifts.index');
    Route::get('/emp-punches', App\Livewire\Hrms\Attendance\EmpPunches::class)->name('emp-punches.index');
    Route::get('/todays-attendance', App\Livewire\Hrms\Attendance\TodayAttendanceStats::class)->name('todays-attendance');
    Route::get('/employee-attendance', App\Livewire\Hrms\Attendance\EmployeeAttendance::class)->name('hrms.attendance.employee-attendance');
    Route::get('/attendance-bulk-upload', App\Livewire\Hrms\Attendance\AttendanceBulkUpload::class)->name('hrms.attendance.attendance-bulk-upload');
    Route::get('/emp-attendance-status', App\Livewire\Hrms\Attendance\EmpAttendanceStatus::class)->name('hrms.attendance.emp-attendance-status');
    Route::get('/emp-leave-allocation', App\Livewire\Hrms\Attendance\EmpLeaveAllocation::class)->name('hrms.attendance.emp-leave-allocation');
    Route::get('/leaves-quota-templates', App\Livewire\Hrms\Attendance\LeavesQuotaTemplates::class)->name('hrms.attendance.leaves-quota-templates');
    Route::get('/monthly-emp-attendance', App\Livewire\Hrms\Attendance\MonthlyEmpAttendance::class)->name('hrms.attendance.monthly-emp-attendance');
    Route::get('/view-emp-attendances', App\Livewire\Hrms\Attendance\ViewEmpAttendances::class)->name('hrms.attendance.view-emp-attendances');
    Route::get('/work-breaks', App\Livewire\Hrms\Attendance\WorkBreaks::class)->name('hrms.attendance.work-breaks');
    Route::get('/work-shifts', App\Livewire\Hrms\Attendance\WorkShifts::class)->name('hrms.attendance.work-shifts');
});

// Attendance Meta Routes
Route::prefix('hrms/attendance-meta')->middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/view-punches', App\Livewire\Hrms\AttendanceMeta\ViewPunches::class)->name('hrms.attendance-meta.view-punches');
});
