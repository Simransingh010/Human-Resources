<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Saas\Permission;
use App\Models\Saas\App;
use App\Models\Saas\AppModule;
use Illuminate\Support\Collection;
class Sidebar extends Component
{
    public $apps = [];
    public $selectedApp = null;
    public $modules = [];

    public function mount()
    {
        $this->loadApps();

        // If session has a previously selected app, use it
        $this->selectedApp = session('selected_app_id');

        // If no selected app, use the first app from the list
        if (!$this->selectedApp && count($this->apps) > 0) {
            $this->selectedApp = $this->apps[0]['id'];
//            dd($this->selectedApp);
            session(['selected_app_id' => $this->selectedApp]);
        }

        // Load modules for the selected app
        if ($this->selectedApp) {
            $this->loadModulesForApp($this->selectedApp);
        }
        // Load components for the first module.. will implment it later as multi-redirect issus is coming
//        if (!empty($this->modules)) {
//            $firstModuleId = $this->modules[0]['id'];
//            $this->loadmodule($firstModuleId);
//        }


    }

    public function loadApps()
    {
        $firmId = session('firm_id');
        $panelId = session('panel_id');
        $user = auth()->user();

        if (!$user->firms()->whereKey($firmId)->exists() || !$user->panels()->whereKey($panelId)->exists()) {
//            abort(403, 'Unauthorized access.');
        }

        $userPermissionIds = $user->permissions()->pluck('permissions.id')->toArray();

        $groupPermissionIds = $user->permissionGroups()
            ->with('permissions:id')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('id')
            ->toArray();
        // Permission are being merged, as If we have assigned the roles , permission of all that role will applicable to that user
        $allPermissionIds = collect($userPermissionIds)
            ->merge($groupPermissionIds)
            ->unique()
            ->values();

//        dd($panelId);

        $permissions = Permission::with(['app_module.app'])
            ->whereHas('app_module', function ($query) use ($panelId) {
                $query->whereHas('app', function ($q) use ($panelId) {
                    $q->whereHas('panels', fn($p) => $p->whereKey($panelId));
                });
            })
            ->whereIn('id', $allPermissionIds)
            ->where('is_inactive', 0)
            ->get();

        $permissions=$this->getSaasAppPermissions();

//        dd($permissions);
// Store all permissions to be filtered later
        session(['user_permissions' => $permissions]);

// Extract unique apps
        $this->apps = $permissions->map(fn($p) => $p->app_module->app)
            ->unique('id')
            ->values()
            ->map(fn($app) => [
                'id' => $app->id,
                'name' => $app->name,
                'code' => $app->code,
                'icon' => $app->icon,
                'route' => $app->route,
            ]);
    }

    public function getModulesSelectedApp($appId)
    {
        $this->selectedApp = $appId;
        session(['selected_app_id' => $appId]);
        $this->loadModulesForApp($appId);
    }

    public function loadModulesForApp($appId)
    {
        $permissions = collect(session('user_permissions'));

        $filtered = $permissions->filter(fn($perm) => $perm->app_module->app->id == $appId);

//        $this->modules = $filtered->groupBy('app_module.id')->map(function ($perms, $moduleId) {
//            $module = $perms->first()->app_module;
//
//            return [
//                'id' => $module->id,
//                'name' => $module->name,
//                'code' => $module->code,
//                'icon' => $module->icon,
//                'route' => $module->route,
//                'permissions' => $perms->map(fn($p) => [
//                    'id' => $p->id,
//                    'name' => $p->name,
//                    'code' => $p->code,
//                    'icon' => $p->icon,
//                    'route' => $p->route,
//                ])->values()
//            ];
//        })->values();

        $this->modules = $filtered->groupBy(fn($perm) => $perm->app_module->id)->map(function ($perms, $moduleId) {
            $module = $perms->first()->app_module;

            return [
                'id' => $module->id,
                'name' => $module->name,
                'code' => $module->code,
                'icon' => $module->icon,
                'route' => $module->route,
                'permissions' => $perms->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'icon' => $p->icon,
                        'route' => $p->route,
                    ];
                })->values()
            ];
        })->values();
        dd( $this->modules);

// Save selected app permissions in session
        session(['current_app_permissions' => $filtered->pluck('id')->values()]);
    }

    public function loadmodule($moduleId)
    {
        // Find the module from current list
        $module = collect($this->modules)->firstWhere('id', $moduleId);

        if (!$module) {
            session()->flash('error', 'Module not found.');
            return;
        }

        // Store the module info and its permissions in the session
        session([
            'current_module' => [
                'id' => $module['id'],
                'name' => $module['name'],
                'code' => $module['code'],
                'icon' => $module['icon'],
                'route' => $module['route'],
            ],
            'current_module_permissions' => $module['permissions'],
        ]);

        // Redirect to module route
        return redirect()->route($module['route']);
    }

    public function render()
    {
        return view('livewire.panel.sidebar');
    }

    public function getSaasAppPermissions(): Collection
    {
        // Static apps
        $apps = [
            1 => new App([
                'id' => 1,
                'name' => 'SAAS',
                'code' => 'HRM',
                'description' => 'Human Resource Management System',
                'order' => 1,
                'is_inactive' => 0,
            ]),
            2 => new App([
                'id' => 2,
                'name' => 'FINANCE',
                'code' => 'FIN',
                'description' => 'Finance & Accounting',
                'order' => 2,
                'is_inactive' => 0,
            ]),
        ];

        // Modules
        $modules = [
            1 => new AppModule([
                'id' => 1,
                'app_id' => 1,
                'name' => 'Agency',
                'code' => 'AGN',
                'description' => 'Agency Module',
                'route' => 'hrms.onboard.employee',
                'order' => 1,
                'is_inactive' => 0,
            ]),
            2 => new AppModule([
                'id' => 2,
                'app_id' => 2,
                'name' => 'Firms',
                'code' => 'FIRM',
                'description' => 'Firms',
                'route' => 'hrms.onboard.employee',
                'order' => 1,
                'is_inactive' => 0,
            ]),
        ];

        // Link modules to apps
        $modules[1]->setRelation('app', $apps[1]);
        $modules[2]->setRelation('app', $apps[2]);

        // Components
        $permissions = collect([
            new Permission([
                'id' => 1,
                'app_module_id' => 1,
                'name' => 'Employee Attendance',
                'title' => 'View Attendance',
                'description' => 'View and manage attendance',
                'route' => 'hrms.onboard.employee',
                'order' => 1,
                'is_inactive' => 0,
            ]),
            new Permission([
                'id' => 2,
                'app_module_id' => 2,
                'name' => 'Invoice Management',
                'title' => 'Manage Invoices',
                'description' => 'Access and manage finance invoices',
                'route' => 'hrms.onboard.employee',
                'order' => 1,
                'is_inactive' => 0,
            ]),
        ]);

        // Attach module to each permission
        $permissions->each(function ($permission) use ($modules) {
            $permission->setRelation('app_module', $modules[$permission->app_module_id]);
        });

        // ✅ Return flat collection
        return $permissions;
    }

}
