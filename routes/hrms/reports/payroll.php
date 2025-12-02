<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/reports/payroll-reports')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/payroll-reports', App\Livewire\Hrms\Reports\PayrollReports\PayrollReports::class)->name('hrms.reports.payroll-reports.payroll-reports');
    Route::get('/bank-report', App\Livewire\Hrms\Reports\PayrollReports\BankReport::class)->name('hrms.reports.payroll-reports.bank-report');
    Route::get('/tds-report', App\Livewire\Hrms\Reports\PayrollReports\TdsReport::class)->name('hrms.reports.payroll-reports.tds-report');
    Route::get('/epf-report', App\Livewire\Hrms\Reports\PayrollReports\EpfReport::class)->name('hrms.reports.payroll-reports.epf-report');
    Route::get('/ecis-report', App\Livewire\Hrms\Reports\PayrollReports\EcisReport::class)->name('hrms.reports.payroll-reports.ecis-report');
    Route::get('/nps-report', App\Livewire\Hrms\Reports\PayrollReports\NpsReport::class)->name('hrms.reports.payroll-reports.nps-report');
});
