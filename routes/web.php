<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Core application routes. Module-specific routes are loaded from
| separate files in the routes/ directory for better organization.
|
*/

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::get('iim-sirmaur', function () {
    return view('index-page');
})->name('iim-sirmaur');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Fallback route for wire-based navigation
Route::get('/panel', function () {
    return view('layouts.panel-screen', ['component' => null]);
})
->middleware(['auth', 'initialize.session'])
->name('panel');

// Universal hybrid screen route - supports any Livewire component via URL
Route::middleware(['auth', 'initialize.session'])->group(function () {
    Route::get('/screen/{component}', function ($component) {
        return view('layouts.panel-screen', ['component' => $component]);
    })
    ->where('component', '[a-zA-Z0-9\.\-]+')
    ->name('panel.screen');
});

// Legacy dashboard route (keeping for backward compatibility)
Route::get('onboard-dashboard', function () {
    return view('layouts.panel-screen', ['component' => 'hrms.onboard.onboard-dashboard']);
})->middleware(['auth', 'initialize.session'])->name('onboard-dashboard');

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
*/

// HRMS Module Routes
require base_path('routes/hrms/onboard.php');
require base_path('routes/hrms/payroll.php');
require base_path('routes/hrms/attendance.php');
require base_path('routes/hrms/offboard.php');
require base_path('routes/hrms/taxation.php');
require base_path('routes/hrms/students.php');
require base_path('routes/hrms/workshifts.php');
require base_path('routes/hrms/workshift_meta.php');
require base_path('routes/hrms/employees_meta.php');
require base_path('routes/hrms/leave.php');

// HRMS Reports
require base_path('routes/hrms/reports/attendance.php');
require base_path('routes/hrms/reports/payroll.php');

// SaaS Module Routes
require base_path('routes/saas.php');

// Settings Routes
require base_path('routes/settings.php');

// Auth Routes
require __DIR__.'/auth.php';
