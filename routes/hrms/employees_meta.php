<?php

use Illuminate\Support\Facades\Route;

// Legacy employee meta routes (keeping old paths for backward compatibility)
Route::middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/hrms/employee-addresses', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-addresses']))->name('hrms.employees-meta.employee-addresses-legacy');
    Route::get('/hrms/employee-bank-accounts', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-bank-accounts']))->name('hrms.employees-meta.employee-bank-accounts-legacy');
    Route::get('/hrms/employee-contacts', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-contacts']))->name('hrms.employees-meta.employee-contacts-legacy');
    Route::get('/hrms/employee-docs', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-docs']))->name('hrms.employees-meta.employee-docs-legacy');
    Route::get('/hrms/employee-job-profiles', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-job-profiles']))->name('hrms.employees-meta.employee-job-profiles-legacy');
    Route::get('/hrms/employee-personal-details', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-personal-details']))->name('hrms.employees-meta.employee-personal-details-legacy');
    Route::get('/hrms/employee-relations', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-relations']))->name('hrms.employees-meta.employee-relations-legacy');
});

// Employees Meta Routes (new structure)
Route::prefix('hrms/employees-meta')->middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/employee-attendance-policy', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-attendance-policy']))->name('hrms.employees-meta.employee-attendance-policy');
    Route::get('/employee-work-shift', fn() => view('layouts.panel-screen', ['component' => 'hrms.employees-meta.employee-work-shift']))->name('hrms.employees-meta.employee-work-shift');
});
