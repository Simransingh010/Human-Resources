<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/attendance')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/attendance-policies', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.attendance-policies']))->name('hrms.attendance.attendance-policies');
    Route::get('/leave-types', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.leave-types']))->name('hrms.attendance.leave-types');
    Route::get('/leaves-quota-template-setups', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.leaves-quota-template-setups']))->name('hrms.attendance.leaves-quota-template-setups');
    Route::get('/emp-leave-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-leave-allocations']))->name('hrms.attendance.emp-leave-allocations');
    Route::get('/emp-leave-request-logs', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-leave-request-logs']))->name('hrms.attendance.emp-leave-request-logs');
    Route::get('/work-shift-days', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.work-shift-days']))->name('hrms.attendance.work-shift-days');
    Route::get('/work-shift-days-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.work-shift-days-breaks']))->name('hrms.attendance.work-shift-days-breaks');
    Route::get('/work-shifts-algos', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.work-shifts-algos']))->name('hrms.attendance.work-shifts-algos');
    Route::get('/emp-leave-requests', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-leave-requests']))->name('hrms.attendance.emp-leave-requests');
    Route::get('/emp-attendances', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-attendances']))->name('hrms.attendance.emp-attendances');
    Route::get('/emp-work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-work-shifts']))->name('hrms.attendance.emp-work-shifts');
    Route::get('/emp-punches', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-punches']))->name('hrms.attendance.emp-punches');
    Route::get('/todays-attendance', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.today-attendance-stats']))->name('hrms.attendance.todays-attendance');
    Route::get('/employee-attendance', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.employee-attendance']))->name('hrms.attendance.employee-attendance');
    Route::get('/attendance-bulk-upload', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.attendance-bulk-upload']))->name('hrms.attendance.attendance-bulk-upload');
    Route::get('/emp-attendance-status', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-attendance-status']))->name('hrms.attendance.emp-attendance-status');
    Route::get('/emp-leave-allocation', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.emp-leave-allocation']))->name('hrms.attendance.emp-leave-allocation');
    Route::get('/leaves-quota-templates', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.leaves-quota-templates']))->name('hrms.attendance.leaves-quota-templates');
    Route::get('/monthly-emp-attendance', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.monthly-emp-attendance']))->name('hrms.attendance.monthly-emp-attendance');
    Route::get('/view-emp-attendances', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.view-emp-attendances']))->name('hrms.attendance.view-emp-attendances');
    Route::get('/work-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.work-breaks']))->name('hrms.attendance.work-breaks');
    Route::get('/work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance.work-shifts']))->name('hrms.attendance.work-shifts');
});

// Attendance Meta Routes
Route::prefix('hrms/attendance-meta')->middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/view-punches', fn() => view('layouts.panel-screen', ['component' => 'hrms.attendance-meta.view-punches']))->name('hrms.attendance-meta.view-punches');
});
