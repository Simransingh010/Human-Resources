<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/work-shift-meta')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/work-shift-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shift-meta.work-shift-breaks']))->name('hrms.work-shift-meta.work-shift-breaks');
    Route::get('/work-shift-days', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shift-meta.work-shift-days']))->name('hrms.work-shift-meta.work-shift-days');
    Route::get('/work-shift-days-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shift-meta.work-shift-days-breaks']))->name('hrms.work-shift-meta.work-shift-days-breaks');
    Route::get('/work-shift-day-statuses', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shift-meta.work-shift-day-statuses']))->name('hrms.work-shift-meta.work-shift-day-statuses');
    Route::get('/work-shifts-algos', fn() => view('layouts.panel-screen', ['component' => 'hrms.work-shift-meta.work-shifts-algos']))->name('hrms.work-shift-meta.work-shifts-algos');
});
