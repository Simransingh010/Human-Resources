<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/offboard')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/employee-exit-initiations', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.employee-exit-initiations']))->name('hrms.offboard.employee-exit-initiations');
    Route::get('/exit-approval-actions', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.exit-approval-actions']))->name('hrms.offboard.exit-approval-actions');
    Route::get('/exit-approvals', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.exit-approvals']))->name('hrms.offboard.exit-approvals');
    Route::get('/exit-approval-steps', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.exit-approval-steps']))->name('hrms.offboard.exit-approval-steps');
    Route::get('/exit-interviews', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.exit-interviews']))->name('hrms.offboard.exit-interviews');
    Route::get('/final-settlement-items', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.final-settlement-items']))->name('hrms.offboard.final-settlement-items');
    Route::get('/final-settlements', fn() => view('layouts.panel-screen', ['component' => 'hrms.offboard.final-settlements']))->name('hrms.offboard.final-settlements');
});
