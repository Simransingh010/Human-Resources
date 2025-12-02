<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'initialize.session'])->group(function () {
    
    Route::get('/agencies', App\Livewire\Saas\Agencies\Index::class)->name('agencies.index');
    
    Route::prefix('saas')->group(function () {
        Route::get('/firms', App\Livewire\Saas\Firms::class)->name('saas.firms');
        Route::get('/users', App\Livewire\Saas\Users::class)->name('saas.users');
        Route::get('/panels', App\Livewire\Saas\Panels::class)->name('saas.panels');
        Route::get('/apps', App\Livewire\Saas\Apps::class)->name('saas.apps');
        Route::get('/versions', App\Livewire\Saas\Versions::class)->name('saas.versions');
        Route::get('/module-groups', App\Livewire\Saas\ModuleGroups::class)->name('saas.module-groups');
        Route::get('/actionclusters', App\Livewire\Saas\Actionclusters::class)->name('saas.actionclusters');
        Route::get('/moduleclusters', App\Livewire\Saas\Moduleclusters::class)->name('saas.moduleclusters');
        Route::get('/componentclusters', App\Livewire\Saas\Componentclusters::class)->name('saas.componentclusters');
        Route::get('/panel-structuring', App\Livewire\Saas\PanelStructuring::class)->name('saas.panel-structuring');
        Route::get('/permissions', App\Livewire\Saas\Components::class)->name('saas.permissions');
        Route::get('/permission-groups', App\Livewire\Saas\PermissionGroups::class)->name('saas.permission-groups');
        Route::get('/app-modules', App\Livewire\Saas\AppsMeta\AppModules::class)->name('saas.app-modules');
        Route::get('/firm-branding', App\Livewire\Saas\FirmBrandings::class)->name('saas.firm-branding');
        Route::get('/actions', App\Livewire\Saas\Actions::class)->name('saas.actions');
        Route::get('/admin-users', App\Livewire\Saas\AdminUsers::class)->name('saas.admin-users');
        Route::get('/agencies-list', App\Livewire\Saas\Agencies::class)->name('saas.agencies-list');
        Route::get('/college-employees', App\Livewire\Saas\CollegeEmployees::class)->name('saas.college-employees');
        Route::get('/colleges', App\Livewire\Saas\Colleges::class)->name('saas.colleges');
        Route::get('/components', App\Livewire\Saas\Components::class)->name('saas.components');
        Route::get('/modules', App\Livewire\Saas\Modules::class)->name('saas.modules');
        Route::get('/permissions-list', App\Livewire\Saas\Permissions::class)->name('saas.permissions-list');
        Route::get('/role-action-sync', App\Livewire\Saas\RoleActionSync::class)->name('saas.role-action-sync');
        Route::get('/roles', App\Livewire\Saas\Roles::class)->name('saas.roles');
    });

    // Apps Meta
    Route::prefix('saas/apps-meta')->group(function () {
        Route::get('/apps-module-sync', App\Livewire\Saas\AppsMeta\AppsModuleSync::class)->name('saas.apps-meta.apps-module-sync');
    });
    
    // Firm Meta
    Route::prefix('saas/firm-meta')->group(function () {
        Route::get('/app-access', App\Livewire\Saas\FirmMeta\AppAccess::class)->name('saas.firm-meta.app-access');
    });
    
    // Module Meta
    Route::prefix('saas/module-meta')->group(function () {
        Route::get('/component-sync', App\Livewire\Saas\ModuleMeta\ComponentSync::class)->name('saas.module-meta.component-sync');
    });
    
    // Panel Meta
    Route::prefix('saas/panel-meta')->group(function () {
        Route::get('/app-sync', App\Livewire\Saas\PanelMeta\AppSync::class)->name('saas.panel-meta.app-sync');
        Route::get('/component-sync', App\Livewire\Saas\PanelMeta\ComponentSync::class)->name('saas.panel-meta.component-sync');
        Route::get('/module-sync', App\Livewire\Saas\PanelMeta\ModuleSync::class)->name('saas.panel-meta.module-sync');
    });
    
    // Permission Meta
    Route::prefix('saas/permission-meta')->group(function () {
        Route::get('/permission-sync', App\Livewire\Saas\PermissionMeta\PermissionSync::class)->name('saas.permission-meta.permission-sync');
    });
    
    // User Meta
    Route::prefix('saas/user-meta')->group(function () {
        Route::get('/firm-sync', App\Livewire\Saas\UserMeta\FirmSync::class)->name('saas.user-meta.firm-sync');
        Route::get('/panel-sync', App\Livewire\Saas\UserMeta\PanelSync::class)->name('saas.user-meta.panel-sync');
        Route::get('/permission-group-sync', App\Livewire\Saas\UserMeta\PermissionGroupSync::class)->name('saas.user-meta.permission-group-sync');
        Route::get('/permission-sync', App\Livewire\Saas\UserMeta\PermissionSync::class)->name('saas.user-meta.permission-sync');
    });
});
