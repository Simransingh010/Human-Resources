<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/reports/attendance-reports')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/attendance-register', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.attendance-register']))->name('hrms.reports.attendance-reports.attendance-register');
    Route::get('/attendance-summary', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.attendance-summary']))->name('hrms.reports.attendance-reports.attendance-summary');
    Route::get('/week-off-summary', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.week-off-summary']))->name('hrms.reports.attendance-reports.week-off-summary');
    Route::get('/detailed-week-off-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.detailed-week-off-report']))->name('hrms.reports.attendance-reports.detailed-week-off-report');
    Route::get('/leave-summary-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.leave-summary-report']))->name('hrms.reports.attendance-reports.leave-summary-report');
    Route::get('/eu-attendance-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.attendance-reports.eu-attendance-report']))->name('hrms.reports.attendance-reports.eu-attendance-report');
});
