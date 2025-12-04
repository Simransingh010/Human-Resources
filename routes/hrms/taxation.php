<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/taxation')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/declaration-groups', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.declaration-groups']))->name('hrms.taxation.declaration-groups');
    Route::get('/declaration-types', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.declaration-types']))->name('hrms.taxation.declaration-types');
    Route::get('/emp-home-loan-records', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.emp-home-loan-records']))->name('hrms.taxation.emp-home-loan-records');
    Route::get('/emp-hra-details', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.emp-hra-details']))->name('hrms.taxation.emp-hra-details');
    Route::get('/emp-itr-returns', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.emp-itr-returns']))->name('hrms.taxation.emp-itr-returns');
    Route::get('/employee-salary-details', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.employee-salary-details']))->name('hrms.taxation.employee-salary-details');
    Route::get('/emp-tax-declarations', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.emp-tax-declarations']))->name('hrms.taxation.emp-tax-declarations');
    Route::get('/loss-cf', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.loss-cf']))->name('hrms.taxation.loss-cf');
    Route::get('/tax-calculator', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.tax-calculator']))->name('hrms.taxation.tax-calculator');
    Route::get('/tax-payments', fn() => view('layouts.panel-screen', ['component' => 'hrms.taxation.tax-payments']))->name('hrms.taxation.tax-payments');
});
