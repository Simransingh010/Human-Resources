<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/payroll')->middleware(['auth', 'initialize.session'])->group(function () {
    
    // Panel-screen based routes
    Route::get('/payroll-cycles', function () {
        return view('layouts.panel-screen', ['component' => 'hrms.payroll.payroll-cycles']);
    })->name('hrms.payroll.payroll-cycles');
    
    Route::get('/bulk-employee-salary-components', function () {
        return view('layouts.panel-screen', ['component' => 'hrms.payroll.bulk-employee-salary-components']);
    })->name('hrms.payroll.bulk-employee-salary-components');
    
    // Livewire component routes
    Route::get('/week-off-credit', App\Livewire\Hrms\Payroll\WeekOffCredit::class)->name('hrms.payroll.week-off-credit');
    Route::get('/week-offs', App\Livewire\Hrms\Payroll\WeekOffs::class)->name('hrms.payroll.week-offs');
    Route::get('/attendance-payroll-step', App\Livewire\Hrms\Payroll\AttendancePayrollStep::class)->name('hrms.payroll.attendance-payroll-step');
    Route::get('/bulk-employee-salary-components-screen', App\Livewire\Hrms\Payroll\BulkEmployeeSalaryComponents::class)->name('hrms.payroll.bulk-employee-salary-components-screen');
    Route::get('/employees-salary-execution-groups', App\Livewire\Hrms\Payroll\EmployeesSalaryExecutionGroups::class)->name('hrms.payroll.employees-salary-execution-groups');
    Route::get('/employees-tax-regime', App\Livewire\Hrms\Payroll\EmployeesTaxRegime::class)->name('hrms.payroll.employees-tax-regime');
    Route::get('/employee-tax-components', App\Livewire\Hrms\Payroll\EmployeeTaxComponents::class)->name('hrms.payroll.employee-tax-components');
    Route::get('/emp-salary-tracks', App\Livewire\Hrms\Payroll\EmpSalaryTracks::class)->name('hrms.payroll.emp-salary-tracks');
    Route::get('/lop-adjustment-step', App\Livewire\Hrms\Payroll\LopAdjustmentStep::class)->name('hrms.payroll.lop-adjustment-step');
    Route::get('/override-amounts', App\Livewire\Hrms\Payroll\OverrideAmounts::class)->name('hrms.payroll.override-amounts');
    Route::get('/override-head-amount-manually', App\Livewire\Hrms\Payroll\OverrideHeadAmountManually::class)->name('hrms.payroll.override-head-amount-manually');
    Route::get('/override-unknown-component', App\Livewire\Hrms\Payroll\OverRideUnkonwnComponent::class)->name('hrms.payroll.override-unknown-component');
    Route::get('/payroll-cycles-screen', App\Livewire\Hrms\Payroll\PayrollCycles::class)->name('hrms.payroll.payroll-cycles-screen');
    Route::get('/payroll-steps', App\Livewire\Hrms\Payroll\PayrollSteps::class)->name('hrms.payroll.payroll-steps');
    Route::get('/review-override-components', App\Livewire\Hrms\Payroll\ReviewOverrideComponents::class)->name('hrms.payroll.review-override-components');
    Route::get('/salary-advances', App\Livewire\Hrms\Payroll\SalaryAdvances::class)->name('hrms.payroll.salary-advances');
    Route::get('/salary-advances-step', App\Livewire\Hrms\Payroll\SalaryAdvancesStep::class)->name('hrms.payroll.salary-advances-step');
    Route::get('/salary-arrears', App\Livewire\Hrms\Payroll\SalaryArrears::class)->name('hrms.payroll.salary-arrears');
    Route::get('/salary-arrears-step', App\Livewire\Hrms\Payroll\SalaryArrearsStep::class)->name('hrms.payroll.salary-arrears-step');
    Route::get('/salary-component-employees', App\Livewire\Hrms\Payroll\SalaryComponentEmployees::class)->name('hrms.payroll.salary-component-employees');
    Route::get('/salary-component-groups', App\Livewire\Hrms\Payroll\SalaryComponentGroups::class)->name('hrms.payroll.salary-component-groups');
    Route::get('/salary-components', App\Livewire\Hrms\Payroll\SalaryComponents::class)->name('hrms.payroll.salary-components');
    Route::get('/salary-components-employees', App\Livewire\Hrms\Payroll\SalaryComponentsEmployees::class)->name('hrms.payroll.salary-components-employees');
    Route::get('/salary-cycles', App\Livewire\Hrms\Payroll\SalaryCycles::class)->name('hrms.payroll.salary-cycles');
    Route::get('/salary-execution-groups', App\Livewire\Hrms\Payroll\SalaryExecutionGroups::class)->name('hrms.payroll.salary-execution-groups');
    Route::get('/salary-holds', App\Livewire\Hrms\Payroll\SalaryHolds::class)->name('hrms.payroll.salary-holds');
    Route::get('/salary-increment', App\Livewire\Hrms\Payroll\SalaryIncrement::class)->name('hrms.payroll.salary-increment');
    Route::get('/salary-template-allocations', App\Livewire\Hrms\Payroll\SalaryTemplateAllocations::class)->name('hrms.payroll.salary-template-allocations');
    Route::get('/salary-templates', App\Livewire\Hrms\Payroll\SalaryTemplates::class)->name('hrms.payroll.salary-templates');
    Route::get('/salary-templates-components', App\Livewire\Hrms\Payroll\SalaryTemplatesComponents::class)->name('hrms.payroll.salary-templates-components');
    Route::get('/set-head-amount-manually', App\Livewire\Hrms\Payroll\SetHeadAmountManually::class)->name('hrms.payroll.set-head-amount-manually');
    Route::get('/static-unknown-components', App\Livewire\Hrms\Payroll\StaticUnknownComponents::class)->name('hrms.payroll.static-unknown-components');
    Route::get('/tax-bracket-breakdowns', App\Livewire\Hrms\Payroll\TaxBracketBreakdowns::class)->name('hrms.payroll.tax-bracket-breakdowns');
    Route::get('/tax-brackets', App\Livewire\Hrms\Payroll\TaxBrackets::class)->name('hrms.payroll.tax-brackets');
    Route::get('/tax-rebates', App\Livewire\Hrms\Payroll\TaxRebates::class)->name('hrms.payroll.tax-rebates');
    Route::get('/tax-regimes', App\Livewire\Hrms\Payroll\TaxRegimes::class)->name('hrms.payroll.tax-regimes');
    Route::get('/tds-calculations', App\Livewire\Hrms\Payroll\TdsCalculations::class)->name('hrms.payroll.tds-calculations');
    Route::get('/tds-check-screen', App\Livewire\Hrms\Payroll\TdsCheckScreen::class)->name('hrms.payroll.tds-check-screen');
});
