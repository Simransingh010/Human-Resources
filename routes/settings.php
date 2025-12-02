<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'initialize.session'])->group(function () {
    
    // Location Hierarchy
    Route::prefix('settings/location-hierarchy')->group(function () {
        Route::get('/cities-or-villages', App\Livewire\Settings\LocationHierarchy\CitiesOrVillages::class)->name('settings.location-hierarchy.cities-or-villages');
        Route::get('/countries', App\Livewire\Settings\LocationHierarchy\Countries::class)->name('settings.location-hierarchy.countries');
        Route::get('/districts', App\Livewire\Settings\LocationHierarchy\Districts::class)->name('settings.location-hierarchy.districts');
        Route::get('/postoffices', App\Livewire\Settings\LocationHierarchy\Postoffices::class)->name('settings.location-hierarchy.postoffices');
        Route::get('/states', App\Livewire\Settings\LocationHierarchy\States::class)->name('settings.location-hierarchy.states');
        Route::get('/subdivisions', App\Livewire\Settings\LocationHierarchy\Subdivisions::class)->name('settings.location-hierarchy.subdivisions');
    });
    
    // Onboard Settings
    Route::prefix('settings/onboard-settings')->group(function () {
        Route::get('/departments', App\Livewire\Settings\OnboardSettings\Departments::class)->name('settings.onboard-settings.departments');
        Route::get('/departments-designations', App\Livewire\Settings\OnboardSettings\DepartmentsDesignations::class)->name('settings.onboard-settings.departments-designations');
        Route::get('/designations', App\Livewire\Settings\OnboardSettings\Designations::class)->name('settings.onboard-settings.designations');
        Route::get('/document-types', App\Livewire\Settings\OnboardSettings\DocumentTypes::class)->name('settings.onboard-settings.document-types');
        Route::get('/employment-types', App\Livewire\Settings\OnboardSettings\EmploymentTypes::class)->name('settings.onboard-settings.employment-types');
        Route::get('/joblocations', App\Livewire\Settings\OnboardSettings\Joblocations::class)->name('settings.onboard-settings.joblocations');
    });
    
    // Role Based Access
    Route::prefix('settings/role-based-access')->group(function () {
        Route::get('/bulk-role-assign', App\Livewire\Settings\RoleBasedAccess\BulkRoleAssign::class)->name('settings.role-based-access.bulk-role-assign');
        Route::get('/firm-users', App\Livewire\Settings\RoleBasedAccess\FirmUsers::class)->name('settings.role-based-access.firm-users');
        Route::get('/panel-bulk-assign', App\Livewire\Settings\RoleBasedAccess\PanelBulkAssign::class)->name('settings.role-based-access.panel-bulk-assign');
        Route::get('/role-action-sync', App\Livewire\Settings\RoleBasedAccess\RoleActionSync::class)->name('settings.role-based-access.role-action-sync');
        Route::get('/roles', App\Livewire\Settings\RoleBasedAccess\Roles::class)->name('settings.role-based-access.roles');
    });
});

// Volt routes for settings pages
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});
