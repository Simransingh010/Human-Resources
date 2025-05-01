<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use App\Services\MenuService;

class MenuCoordinator
{
    protected static array $staticApps = [
        [ 'id' => 1, 'name' => 'SaaS', 'wire' => null, 'icon' => '' ],
    ];

    protected static array $staticModulesByApp = [
        1 => [
            [ 'id' => 11, 'name' => 'Auth', 'wire' => null, 'icon' => '' ],
            [ 'id' => 12, 'name' => 'Organization', 'wire' => null, 'icon' => '' ],
            [ 'id' => 13, 'name' => 'Clusters', 'wire' => null, 'icon' => '' ],
            [ 'id' => 14, 'name' => 'Menus', 'wire' => null, 'icon' => '' ],
        ],
    ];

    protected static array $staticWiresByModule = [
        11 => [
            [ 'name' => 'Users', 'wire' => 'saas.users', 'icon'=> '' ],
        ],
        12 => [
            [ 'name' => 'Firm', 'wire' => 'saas.firms', 'icon'=> '' ],
            [ 'name' => 'Agency', 'wire' => 'saas.agencies.index', 'icon'=> '' ],

        ],
        13 => [
            [ 'name' => 'Module Clusters', 'wire' => 'saas.module-groups', 'icon'=> '' ],
            [ 'name' => 'Component Clusters', 'wire' => 'saas.panels', 'icon'=> '' ],
            [ 'name' => 'Action Clusters', 'wire' => 'saas.users', 'icon'=> '' ],
        ],
        14 => [
            [ 'name' => 'Panels', 'wire' => 'saas.panels', 'icon'=> 'https://try.iqdigit.com/images/appicons/629f51cfaea18_Attendance.png' ],
            [ 'name' => 'Apps', 'wire' => 'saas.apps', 'icon'=> '' ],
            [ 'name' => 'Modules', 'wire' => 'saas.modules', 'icon'=> '' ],
            [ 'name' => 'Components', 'wire' => 'saas.components', 'icon'=> '' ],
            [ 'name' => 'Actions', 'wire' => 'saas.actions', 'icon'=> '' ],
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
        return self::selectWire(session('defaultwire'));
    }





    public static function selectModule($moduleId)
    {
        Session::put('selectedModuleId', $moduleId);
        $wires = self::getModuleWires($moduleId);
        $firstWire = $wires[0]['wire'] ?? session('defaultwire');
        self::selectWire($firstWire);
        return $firstWire;
    }

    public static function selectWire($wire)
    {
        Session::put('selectedWire', $wire ?? session('defaultwire'));
    }

    public static function getSelectedWire()
    {
        return Session::get('selectedWire', session('defaultwire'));
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
}
