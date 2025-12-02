<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/onboard')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/employees', function () {
        return view('layouts.panel-screen', ['component' => 'hrms.onboard.employees']);
    })->name('hrms.onboard.employees');
    
    Route::get('/work-breaks', App\Livewire\Hrms\Onboard\WorkBreaks::class)->name('hrms.onboard.work-breaks');
    Route::get('/work-shifts', App\Livewire\Hrms\Onboard\WorkShifts::class)->name('hrms.onboard.work-shifts');
    Route::get('/holiday-calendars', App\Livewire\Hrms\Onboard\HolidayCalendars::class)->name('hrms.onboard.holiday-calendars');
    Route::get('/employee-bulk-upload', App\Livewire\Hrms\Onboard\EmployeeBulkUpload::class)->name('hrms.onboard.employee-bulk-upload');
    Route::get('/profile-bulk-update', App\Livewire\Hrms\Onboard\BulkEmployeeJobProfiles::class)->name('hrms.onboard.profile-bulk-update');
    Route::get('/onboard-employees', App\Livewire\Hrms\Onboard\OnboardEmployees::class)->name('hrms.onboard.onboard-employees');
    Route::get('/employee-addresses', App\Livewire\Hrms\Onboard\EmployeeAddresses::class)->name('hrms.onboard.employee-addresses');
    Route::get('/employee-bank-accounts', App\Livewire\Hrms\Onboard\EmployeeBankAccounts::class)->name('hrms.onboard.employee-bank-accounts');
    Route::get('/employee-contacts', App\Livewire\Hrms\Onboard\EmployeeContacts::class)->name('hrms.onboard.employee-contacts');
    Route::get('/employee-docs', App\Livewire\Hrms\Onboard\EmployeeDocs::class)->name('hrms.onboard.employee-docs');
    Route::get('/employee-job-profiles', App\Livewire\Hrms\Onboard\EmployeeJobProfiles::class)->name('hrms.onboard.employee-job-profiles');
    Route::get('/employee-personal-details', App\Livewire\Hrms\Onboard\EmployeePersonalDetails::class)->name('hrms.onboard.employee-personal-details');
    Route::get('/employee-relations', App\Livewire\Hrms\Onboard\EmployeeRelations::class)->name('hrms.onboard.employee-relations');
    Route::get('/onboard-dashboard', App\Livewire\Hrms\Onboard\OnboardDashboard::class)->name('hrms.onboard.onboard-dashboard');
    Route::get('/onboard-students', App\Livewire\Hrms\Onboard\OnboardStudents::class)->name('hrms.onboard.onboard-students');
    Route::get('/student-bulk-upload', App\Livewire\Hrms\Onboard\StudentBulkUpload::class)->name('hrms.onboard.student-bulk-upload');
    Route::get('/students', App\Livewire\Hrms\Onboard\Students::class)->name('hrms.onboard.students');
    Route::get('/work-shift-allocations', App\Livewire\Hrms\Onboard\WorkShiftAllocations::class)->name('hrms.onboard.work-shift-allocations');
});
