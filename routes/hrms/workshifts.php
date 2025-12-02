<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/work-shifts')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/employees-with-work-shifts', App\Livewire\Hrms\WorkShifts\EmployeesWithWorkShifts::class)->name('hrms.work-shifts.employees-with-work-shifts');
    Route::get('/holiday-calendars', App\Livewire\Hrms\WorkShifts\HolidayCalendars::class)->name('hrms.work-shifts.holiday-calendars');
    Route::get('/holidays', App\Livewire\Hrms\WorkShifts\Holidays::class)->name('hrms.work-shifts.holidays');
    Route::get('/work-breaks', App\Livewire\Hrms\WorkShifts\WorkBreaks::class)->name('hrms.work-shifts.work-breaks');
    Route::get('/work-shift-allocations', App\Livewire\Hrms\WorkShifts\WorkShiftAllocations::class)->name('hrms.work-shifts.work-shift-allocations');
    Route::get('/work-shift-day-statuses', App\Livewire\Hrms\WorkShifts\WorkShiftDayStatuses::class)->name('hrms.work-shifts.work-shift-day-statuses');
    Route::get('/work-shifts', App\Livewire\Hrms\WorkShifts\WorkShifts::class)->name('hrms.work-shifts.work-shifts');
    Route::get('/work-shifts-algos', App\Livewire\Hrms\WorkShifts\WorkShiftsAlgos::class)->name('hrms.work-shifts.work-shifts-algos');
    
    // WorkShifts Meta Routes
    Route::get('/work-shift-meta/emp-work-shifts', App\Livewire\Hrms\WorkShifts\WorkShiftMeta\EmpWorkShifts::class)->name('hrms.work-shifts.work-shift-meta.emp-work-shifts');
    Route::get('/work-shift-meta/work-shift-days', App\Livewire\Hrms\WorkShifts\WorkShiftMeta\WorkShiftDays::class)->name('hrms.work-shifts.work-shift-meta.work-shift-days');
    Route::get('/work-shift-meta/work-shift-days-breaks', App\Livewire\Hrms\WorkShifts\WorkShiftMeta\WorkShiftDaysBreaks::class)->name('hrms.work-shifts.work-shift-meta.work-shift-days-breaks');
});
