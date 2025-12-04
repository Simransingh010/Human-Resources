<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/onboard')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/employees', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employees']))->name('hrms.onboard.employees');
    Route::get('/work-breaks', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.work-breaks']))->name('hrms.onboard.work-breaks');
    Route::get('/work-shifts', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.work-shifts']))->name('hrms.onboard.work-shifts');
    Route::get('/holiday-calendars', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.holiday-calendars']))->name('hrms.onboard.holiday-calendars');
    Route::get('/employee-bulk-upload', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-bulk-upload']))->name('hrms.onboard.employee-bulk-upload');
    Route::get('/profile-bulk-update', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.bulk-employee-job-profiles']))->name('hrms.onboard.profile-bulk-update');
    Route::get('/onboard-employees', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.onboard-employees']))->name('hrms.onboard.onboard-employees');
    Route::get('/employee-addresses', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-addresses']))->name('hrms.onboard.employee-addresses');
    Route::get('/employee-bank-accounts', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-bank-accounts']))->name('hrms.onboard.employee-bank-accounts');
    Route::get('/employee-contacts', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-contacts']))->name('hrms.onboard.employee-contacts');
    Route::get('/employee-docs', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-docs']))->name('hrms.onboard.employee-docs');
    Route::get('/employee-job-profiles', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-job-profiles']))->name('hrms.onboard.employee-job-profiles');
    Route::get('/employee-personal-details', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-personal-details']))->name('hrms.onboard.employee-personal-details');
    Route::get('/employee-relations', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.employee-relations']))->name('hrms.onboard.employee-relations');
    Route::get('/onboard-dashboard', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.onboard-dashboard']))->name('hrms.onboard.onboard-dashboard');
    Route::get('/onboard-students', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.onboard-students']))->name('hrms.onboard.onboard-students');
    Route::get('/student-bulk-upload', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.student-bulk-upload']))->name('hrms.onboard.student-bulk-upload');
    Route::get('/students', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.students']))->name('hrms.onboard.students');
    Route::get('/work-shift-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.onboard.work-shift-allocations']))->name('hrms.onboard.work-shift-allocations');
});
