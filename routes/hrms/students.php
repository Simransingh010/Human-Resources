<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hrms/students')->middleware(['auth', 'initialize.session'])->group(function () {

    Route::get('/attendance-summary', fn() => view('layouts.panel-screen', ['component' => 'hrms.students.attendance-summary']))->name('hrms.students.attendance-summary');
    Route::get('/bulk-study-groups', fn() => view('layouts.panel-screen', ['component' => 'hrms.students.bulk-study-groups']))->name('hrms.students.bulk-study-groups');
    Route::get('/student-profiles', fn() => view('layouts.panel-screen', ['component' => 'hrms.students.student-profiles']))->name('hrms.students.student-profiles');
});
