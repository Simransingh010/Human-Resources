<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/taxation')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/declaration-groups', App\Livewire\Hrms\Taxation\DeclarationGroups::class)->name('hrms.taxation.declaration-groups');
    Route::get('/declaration-types', App\Livewire\Hrms\Taxation\DeclarationTypes::class)->name('hrms.taxation.declaration-types');
    Route::get('/emp-home-loan-records', App\Livewire\Hrms\Taxation\EmpHomeLoanRecords::class)->name('hrms.taxation.emp-home-loan-records');
    Route::get('/emp-hra-details', App\Livewire\Hrms\Taxation\EmpHraDetails::class)->name('hrms.taxation.emp-hra-details');
    Route::get('/emp-itr-returns', App\Livewire\Hrms\Taxation\EmpItrReturns::class)->name('hrms.taxation.emp-itr-returns');
    Route::get('/employee-salary-details', App\Livewire\Hrms\Taxation\EmployeeSalaryDetails::class)->name('hrms.taxation.employee-salary-details');
    Route::get('/emp-tax-declarations', App\Livewire\Hrms\Taxation\EmpTaxDeclarations::class)->name('hrms.taxation.emp-tax-declarations');
    Route::get('/loss-cf', App\Livewire\Hrms\Taxation\LossCf::class)->name('hrms.taxation.loss-cf');
    Route::get('/tax-calculator', App\Livewire\Hrms\Taxation\TaxCalculator::class)->name('hrms.taxation.tax-calculator');
    Route::get('/tax-payments', App\Livewire\Hrms\Taxation\TaxPayments::class)->name('hrms.taxation.tax-payments');
});
