<?php

use Illuminate\Support\Facades\Route;

// Legacy employee meta routes (keeping old paths for backward compatibility)
Route::middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/hrms/employee-addresses', App\Livewire\Hrms\EmployeesMeta\EmployeeAddresses::class)->name('employee-addresses.index');
    Route::get('/hrms/employee-bank-accounts', App\Livewire\Hrms\EmployeesMeta\EmployeeBankAccounts::class)->name('employee-bank-accounts.index');
    Route::get('/hrms/employee-contacts', App\Livewire\Hrms\EmployeesMeta\EmployeeContacts::class)->name('employee-contacts.index');
    Route::get('/hrms/employee-docs', App\Livewire\Hrms\EmployeesMeta\EmployeeDocs::class)->name('employee-docs.index');
    Route::get('/hrms/employee-job-profiles', App\Livewire\Hrms\EmployeesMeta\EmployeeJobProfiles::class)->name('employee-job-profiles.index');
    Route::get('/hrms/employee-personal-details', App\Livewire\Hrms\EmployeesMeta\EmployeePersonalDetails::class)->name('employee-personal-details.index');
    Route::get('/hrms/employee-relations', App\Livewire\Hrms\EmployeesMeta\EmployeeRelations::class)->name('employee-relations.index');
});

// Employees Meta Routes (new structure)
Route::prefix('hrms/employees-meta')->middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/employee-attendance-policy', App\Livewire\Hrms\EmployeesMeta\EmployeeAttendancePolicy::class)->name('hrms.employees-meta.employee-attendance-policy');
    Route::get('/employee-work-shift', App\Livewire\Hrms\EmployeesMeta\EmployeeWorkShift::class)->name('hrms.employees-meta.employee-work-shift');
});
