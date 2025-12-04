<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/payroll')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/payroll-cycles', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.payroll-cycles']))->name('hrms.payroll.payroll-cycles');
    Route::get('/bulk-employee-salary-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.bulk-employee-salary-components']))->name('hrms.payroll.bulk-employee-salary-components');
    Route::get('/week-off-credit', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.week-off-credit']))->name('hrms.payroll.week-off-credit');
    Route::get('/week-offs', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.week-offs']))->name('hrms.payroll.week-offs');
    Route::get('/attendance-payroll-step', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.attendance-payroll-step']))->name('hrms.payroll.attendance-payroll-step');
    Route::get('/bulk-employee-salary-components-screen', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.bulk-employee-salary-components']))->name('hrms.payroll.bulk-employee-salary-components-screen');
    Route::get('/employees-salary-execution-groups', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.employees-salary-execution-groups']))->name('hrms.payroll.employees-salary-execution-groups');
    Route::get('/employees-tax-regime', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.employees-tax-regime']))->name('hrms.payroll.employees-tax-regime');
    Route::get('/employee-tax-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.employee-tax-components']))->name('hrms.payroll.employee-tax-components');
    Route::get('/emp-salary-tracks', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.emp-salary-tracks']))->name('hrms.payroll.emp-salary-tracks');
    Route::get('/lop-adjustment-step', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.lop-adjustment-step']))->name('hrms.payroll.lop-adjustment-step');
    Route::get('/override-amounts', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.override-amounts']))->name('hrms.payroll.override-amounts');
    Route::get('/override-head-amount-manually', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.override-head-amount-manually']))->name('hrms.payroll.override-head-amount-manually');
    Route::get('/override-unknown-component', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.override-unknown-component']))->name('hrms.payroll.override-unknown-component');
    Route::get('/payroll-cycles-screen', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.payroll-cycles']))->name('hrms.payroll.payroll-cycles-screen');
    Route::get('/payroll-steps', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.payroll-steps']))->name('hrms.payroll.payroll-steps');
    Route::get('/review-override-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.review-override-components']))->name('hrms.payroll.review-override-components');
    Route::get('/salary-advances', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-advances']))->name('hrms.payroll.salary-advances');
    Route::get('/salary-advances-step', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-advances-step']))->name('hrms.payroll.salary-advances-step');
    Route::get('/salary-arrears', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-arrears']))->name('hrms.payroll.salary-arrears');
    Route::get('/salary-arrears-step', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-arrears-step']))->name('hrms.payroll.salary-arrears-step');
    Route::get('/salary-component-employees', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-component-employees']))->name('hrms.payroll.salary-component-employees');
    Route::get('/salary-component-groups', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-component-groups']))->name('hrms.payroll.salary-component-groups');
    Route::get('/salary-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-components']))->name('hrms.payroll.salary-components');
    Route::get('/salary-components-employees', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-components-employees']))->name('hrms.payroll.salary-components-employees');
    Route::get('/salary-cycles', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-cycles']))->name('hrms.payroll.salary-cycles');
    Route::get('/salary-execution-groups', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-execution-groups']))->name('hrms.payroll.salary-execution-groups');
    Route::get('/salary-holds', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-holds']))->name('hrms.payroll.salary-holds');
    Route::get('/salary-increment', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-increment']))->name('hrms.payroll.salary-increment');
    Route::get('/salary-template-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-template-allocations']))->name('hrms.payroll.salary-template-allocations');
    Route::get('/salary-templates', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-templates']))->name('hrms.payroll.salary-templates');
    Route::get('/salary-templates-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.salary-templates-components']))->name('hrms.payroll.salary-templates-components');
    Route::get('/set-head-amount-manually', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.set-head-amount-manually']))->name('hrms.payroll.set-head-amount-manually');
    Route::get('/static-unknown-components', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.static-unknown-components']))->name('hrms.payroll.static-unknown-components');
    Route::get('/tax-bracket-breakdowns', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tax-bracket-breakdowns']))->name('hrms.payroll.tax-bracket-breakdowns');
    Route::get('/tax-brackets', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tax-brackets']))->name('hrms.payroll.tax-brackets');
    Route::get('/tax-rebates', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tax-rebates']))->name('hrms.payroll.tax-rebates');
    Route::get('/tax-regimes', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tax-regimes']))->name('hrms.payroll.tax-regimes');
    Route::get('/tds-calculations', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tds-calculations']))->name('hrms.payroll.tds-calculations');
    Route::get('/tds-check-screen', fn() => view('layouts.panel-screen', ['component' => 'hrms.payroll.tds-check-screen']))->name('hrms.payroll.tds-check-screen');
});
