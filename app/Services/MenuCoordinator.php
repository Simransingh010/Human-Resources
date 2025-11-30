<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use App\Services\MenuService;

class MenuCoordinator
{
    protected static array $staticApps = [
        ['id' => 1, 'name' => 'SaaS', 'wire' => null, 'icon' => ''],
    ];

    protected static array $staticModulesByApp = [
        1 => [

            ['id' => 11, 'name' => 'Platform', 'wire' => null, 'icon' => ''],
            ['id' => 12, 'name' => 'Panels', 'wire' => null, 'icon' => ''],
//            ['id' => 13, 'name' => 'Clusters', 'wire' => null, 'icon' => ''],
            ['id' => 14, 'name' => 'Organization', 'wire' => null, 'icon' => ''],
            ['id' => 15, 'name' => 'Auth', 'wire' => null, 'icon' => ''],
        ],
    ];

    protected static array $staticWiresByModule = [
        11 => [
            ['name' => 'Platform Setup', 'wire' => 'saas.panel-structuring', 'icon' => 'https://try.iqdigit.com/images/appicons/629f51cfaea18_Attendance.png'],
            ['name' => 'Modules', 'wire' => 'saas.modules', 'icon' => ''],
            ['name' => 'Components', 'wire' => 'saas.components', 'icon' => ''],
            ['name' => 'Actions', 'wire' => 'saas.actions', 'icon' => ''],
        ],
        12 => [
            ['name' => 'Panels', 'wire' => 'saas.panels', 'icon' => 'https://try.iqdigit.com/images/appicons/629f51cfaea18_Attendance.png'],
        ],
//        13 => [
//              ['name' => 'Module Clusters', 'wire' => 'saas.moduleclusters', 'icon' => ''],
//            ['name' => 'Component Clusters', 'wire' => 'saas.componentclusters', 'icon' => ''],
//            ['name' => 'Action Clusters', 'wire' => 'saas.actionclusters', 'icon' => ''],
//        ],
        14 => [
            ['name' => 'Firm', 'wire' => 'saas.firms', 'icon' => ''],
            ['name' => 'Agency', 'wire' => 'saas.agencies.index', 'icon' => ''],
        ],
        15 => [
            ['name' => 'Users', 'wire' => 'saas.users', 'icon' => ''],
            ['name' => 'Roles', 'wire' => 'saas.roles', 'icon' => ''],
//            ['name' => 'L1 Users', 'wire' => 'saas.admin-users', 'icon' => ''],
        ],
    ];

    public static function getApps()
    {
        return self::isSaaSPanel() ? self::$staticApps : MenuService::getApps();
    }

    public static function getAppModules($appId)
    {
        return self::isSaaSPanel()
            ? (self::$staticModulesByApp[$appId] ?? [])
            : MenuService::getModulesForApp($appId);
    }

    public static function getModuleWires($moduleId)
    {
        return self::isSaaSPanel()
            ? (self::$staticWiresByModule[$moduleId] ?? [])
            : MenuService::getComponentsForModule($moduleId);
    }

    public static function selectApp($appId)
    {
        Session::put('selectedAppId', $appId);

        $modules = self::getAppModules($appId);

        if (!empty($modules)) {
            $existingSelectedModule = Session::get('selectedModuleId');
            $selectedModule = collect($modules)->firstWhere('id', $existingSelectedModule) ?? $modules[0];
            return self::selectModule($selectedModule['id']);
        }

        self::resetAll();
        return self::selectWire(Session::get('defaultwire'));
    }

    public static function selectModule($moduleId)
    {
        Session::put('selectedModuleId', $moduleId);
        $wires = self::getModuleWires($moduleId);
        $firstWire = $wires[0]['wire'] ?? Session::get('defaultwire');
        self::selectWire($firstWire);
        return $firstWire;
    }

    public static function selectWire($wire)
    {
        \Log::info('MenuCoordinator::selectWire called', [
            'wire' => $wire,
            'before' => Session::get('selectedWire'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ]);
        Session::put('selectedWire', $wire ?? Session::get('defaultwire'));
    }

    public static function getSelectedWire()
    {
        return Session::get('selectedWire', Session::get('defaultwire'));
    }

    public static function getSelectedAppId()
    {
        return Session::get('selectedAppId');
    }

    public static function getSelectedModuleId()
    {
        return Session::get('selectedModuleId');
    }

    public static function resetAll()
    {
        Session::forget(['selectedAppId', 'selectedModuleId', 'selectedWire']);
    }

    public static function getFirstValidWire(array $wires): string
    {
        foreach ($wires as $item) {
            if (!empty($item['wire']) && self::wireExists($item['wire'])) {
                return $item['wire'];
            }
        }
        return session('defaultwire');
    }

    public static function wireExists($wire): bool
    {
        $segments = explode('.', $wire);
        $studlySegments = array_map(fn($segment) => str($segment)->studly(), $segments);
        $className = array_pop($studlySegments);
        $namespacePath = implode('\\', $studlySegments);
        $fullClassName = "App\\Livewire\\{$namespacePath}\\{$className}";

        return class_exists($fullClassName);
    }

    protected static function isSaaSPanel(): bool
    {
        return Session::get('panel_id') === '6';
    }

    /**
     * Check if a wire should use route-based navigation
     * Automatically detects if a named route exists for the wire
     */
    public static function isRouteBased(string $wire): bool
    {
        // Check if a named route exists for this wire
        return \Illuminate\Support\Facades\Route::has($wire);
    }

    /**
     * Get the route URL for a wire if it's route-based
     * Returns null if wire is not route-based
     */
    public static function getRouteUrl(string $wire, ?int $moduleId = null, ?int $appId = null): ?string
    {
        // If a named route exists for this wire, use it
        if (\Illuminate\Support\Facades\Route::has($wire)) {
            return route($wire);
        }

        return null;
    }

    /**
     * Find the moduleId that contains a given wire
     * Returns null if not found
     */
    public static function findModuleIdForWire(string $wire): ?int
    {
        if (self::isSaaSPanel()) {
            foreach (self::$staticWiresByModule as $moduleId => $wires) {
                foreach ($wires as $wireItem) {
                    if ($wireItem['wire'] === $wire) {
                        return $moduleId;
                    }
                }
            }
        } else {
            // For dynamic panels, search through all modules
            $apps = MenuService::getApps();
            foreach ($apps as $app) {
                $modules = MenuService::getModulesForApp($app['id']);
                foreach ($modules as $module) {
                    $components = MenuService::getComponentsForModule($module['id']);
                    foreach ($components as $component) {
                        if ($component['wire'] === $wire) {
                            return $module['id'];
                        }
                    }
                }
            }
        }
        
        return null;
    }
}
