<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/offboard')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/employee-exit-initiations', App\Livewire\Hrms\Offboard\EmployeeExitInitiations::class)->name('hrms.offboard.employee-exit-initiations');
    Route::get('/exit-approval-actions', App\Livewire\Hrms\Offboard\ExitApprovalActions::class)->name('hrms.offboard.exit-approval-actions');
    Route::get('/exit-approvals', App\Livewire\Hrms\Offboard\ExitApprovals::class)->name('hrms.offboard.exit-approvals');
    Route::get('/exit-approval-steps', App\Livewire\Hrms\Offboard\ExitApprovalSteps::class)->name('hrms.offboard.exit-approval-steps');
    Route::get('/exit-interviews', App\Livewire\Hrms\Offboard\ExitInterviews::class)->name('hrms.offboard.exit-interviews');
    Route::get('/final-settlement-items', App\Livewire\Hrms\Offboard\FinalSettlementItems::class)->name('hrms.offboard.final-settlement-items');
    Route::get('/final-settlements', App\Livewire\Hrms\Offboard\FinalSettlements::class)->name('hrms.offboard.final-settlements');
});
