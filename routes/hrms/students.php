<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/students')->middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/attendance-summary', App\Livewire\Hrms\Students\AttendanceSummary::class)->name('hrms.students.attendance-summary');
    Route::get('/bulk-study-groups', App\Livewire\Hrms\Students\BulkStudyGroups::class)->name('hrms.students.bulk-study-groups');
    Route::get('/student-profiles', App\Livewire\Hrms\Students\StudentProfiles::class)->name('hrms.students.student-profiles');
});
