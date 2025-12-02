<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/work-shift-meta')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/work-shift-breaks', App\Livewire\Hrms\WorkShiftMeta\WorkShiftBreaks::class)->name('hrms.work-shift-meta.work-shift-breaks');
    Route::get('/work-shift-days', App\Livewire\Hrms\WorkShiftMeta\WorkShiftDays::class)->name('hrms.work-shift-meta.work-shift-days');
    Route::get('/work-shift-days-breaks', App\Livewire\Hrms\WorkShiftMeta\WorkShiftDaysBreaks::class)->name('hrms.work-shift-meta.work-shift-days-breaks');
    Route::get('/work-shift-day-statuses', App\Livewire\Hrms\WorkShiftMeta\WorkShiftDayStatuses::class)->name('hrms.work-shift-meta.work-shift-day-statuses');
    Route::get('/work-shifts-algos', App\Livewire\Hrms\WorkShiftMeta\WorkShiftsAlgos::class)->name('hrms.work-shift-meta.work-shifts-algos');
});
