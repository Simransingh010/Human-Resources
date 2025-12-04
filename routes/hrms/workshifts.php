<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/work-shifts')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/employees-with-work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.employees-with-work-shifts']))->name('hrms.work-shifts.employees-with-work-shifts');
    Route::get('/holiday-calendars', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.holiday-calendars']))->name('hrms.work-shifts.holiday-calendars');
    Route::get('/holidays', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.holidays']))->name('hrms.work-shifts.holidays');
    Route::get('/work-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-breaks']))->name('hrms.work-shifts.work-breaks');
    Route::get('/work-shift-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shift-allocations']))->name('hrms.work-shifts.work-shift-allocations');
    Route::get('/work-shift-day-statuses', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shift-day-statuses']))->name('hrms.work-shifts.work-shift-day-statuses');
    Route::get('/work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shifts']))->name('hrms.work-shifts.work-shifts');
    Route::get('/work-shifts-algos', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shifts-algos']))->name('hrms.work-shifts.work-shifts-algos');

    // WorkShifts Meta Routes
    Route::get('/work-shift-meta/emp-work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shift-meta.emp-work-shifts']))->name('hrms.work-shifts.work-shift-meta.emp-work-shifts');
    Route::get('/work-shift-meta/work-shift-days', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shift-meta.work-shift-days']))->name('hrms.work-shifts.work-shift-meta.work-shift-days');
    Route::get('/work-shift-meta/work-shift-days-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shifts.work-shift-meta.work-shift-days-breaks']))->name('hrms.work-shifts.work-shift-meta.work-shift-days-breaks');
});
