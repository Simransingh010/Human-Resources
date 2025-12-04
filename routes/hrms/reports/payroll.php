<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/reports/payroll-reports')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/payroll-reports', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.payroll-reports']))->name('hrms.reports.payroll-reports.payroll-reports');
    Route::get('/bank-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.bank-report']))->name('hrms.reports.payroll-reports.bank-report');
    Route::get('/tds-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.tds-report']))->name('hrms.reports.payroll-reports.tds-report');
    Route::get('/epf-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.epf-report']))->name('hrms.reports.payroll-reports.epf-report');
    Route::get('/ecis-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.ecis-report']))->name('hrms.reports.payroll-reports.ecis-report');
    Route::get('/nps-report', fn() => view('layouts.panel-screen', ['component' => 'hrms.reports.payroll-reports.nps-report']))->name('hrms.reports.payroll-reports.nps-report');
});
