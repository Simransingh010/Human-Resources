<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/leave')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/apply-leaves', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.apply-leaves']))->name('hrms.leave.apply-leaves');
    Route::get('/my-leaves', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.my-leaves']))->name('hrms.leave.my-leaves');
    Route::get('/team-leaves', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.team-leaves']))->name('hrms.leave.team-leaves');
    Route::get('/leave-types', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-types']))->name('hrms.leave.leave-types');
    Route::get('/leave-balances', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-balances']))->name('hrms.leave.leave-balances');
    Route::get('/leave-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-allocations']))->name('hrms.leave.leave-allocations');
    Route::get('/leave-rejections', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-rejections']))->name('hrms.leave.leave-rejections');
    Route::get('/leave-approval-rules', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-approval-rules']))->name('hrms.leave.leave-approval-rules');
    Route::get('/leaves-quota-templates', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leaves-quota-templates']))->name('hrms.leave.leaves-quota-templates');
    Route::get('/leaves-quota-templates-setups', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leaves-quota-templates-setups']))->name('hrms.leave.leaves-quota-templates-setups');
    Route::get('/emp-leave-requests', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-requests']))->name('hrms.leave.emp-leave-requests');
    Route::get('/emp-leave-balances', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-balances']))->name('hrms.leave.emp-leave-balances');
    Route::get('/emp-leave-allocations', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-allocations']))->name('hrms.leave.emp-leave-allocations');
    Route::get('/batch-items', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.batch-items']))->name('hrms.leave.batch-items');
    
    // Leave Meta Routes
    Route::get('/emp-leave-balance/emp-leave-transactions', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-balance.emp-leave-transactions']))->name('hrms.leave.emp-leave-balance.emp-leave-transactions');
    Route::get('/emp-leave-requests/emp-leave-request-approvals', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-requests.emp-leave-request-approvals']))->name('hrms.leave.emp-leave-requests.emp-leave-request-approvals');
    Route::get('/emp-leave-requests/leave-request-events', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.emp-leave-requests.leave-request-events']))->name('hrms.leave.emp-leave-requests.leave-request-events');
    Route::get('/leave-approval-rules/department-leave-approval-rules', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-approval-rules.department-leave-approval-rules']))->name('hrms.leave.leave-approval-rules.department-leave-approval-rules');
    Route::get('/leave-approval-rules/employee-leave-approval-rules', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-approval-rules.employee-leave-approval-rules']))->name('hrms.leave.leave-approval-rules.employee-leave-approval-rules');
    Route::get('/leave-meta/emp-leave-request-logs', fn() => view('layouts.panel-screen', ['component' => 'hrms.leave.leave-meta.emp-leave-request-logs']))->name('hrms.leave.leave-meta.emp-leave-request-logs');
});
