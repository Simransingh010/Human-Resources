<?php

namespace App\Services;

use App\Models\Saas\App;
use App\Models\Saas\Module;
use App\Models\Saas\Component;

class MenuService
{
    public static function getApps()
    {
        return App::where('is_inactive','0')
            ->orderBy('order') // Add ordering by the 'order' column
            ->get(['id', 'name', 'wire','icon'])
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'wire' => $app->wire ?? session('defaultwire'),
                    'icon' => $app->icon,
                ];
            })
            ->toArray();
    }

    public static function getModulesForApp_original($appId)
    {
        return Module::whereHas('apps', function ($query) use ($appId) {
            $query->where('app_id', $appId);
        })
            ->orderBy('order') // Add ordering by the 'order' column
            ->get(['id', 'name', 'wire','icon'])
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'wire' => $module->wire ?? session('defaultwire'),
                    'icon' => $module->icon,
                ];
            })
            ->toArray();
    }

    public static function getModulesForApp($appId)
    {
        // List of specific user IDs
        $specificUserIds = [222, 223, 225, 15];

        // Check if the user ID is in the specific list and the appId is 3
        if (in_array(auth()->id(), $specificUserIds)) {
            return Module::whereHas('apps', function ($query) use ($appId) {
                $query->where('app_id', $appId);
            })
                ->whereIn('id', [2,7,9])  // Filter for module with ID 9
                ->orderBy('order')
                ->get(['id', 'name', 'wire', 'icon'])
                ->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'wire' => $module->wire ?? session('defaultwire'),
                        'icon' => $module->icon,
                    ];
                })
                ->toArray();
        } else {
            // Default behavior for other appId values or user ID not in the specific list
            return Module::whereHas('apps', function ($query) use ($appId) {
                $query->where('app_id', $appId);
            })
                ->orderBy('order')
                ->get(['id', 'name', 'wire', 'icon'])
                ->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'wire' => $module->wire ?? session('defaultwire'),
                        'icon' => $module->icon,
                    ];
                })
                ->toArray();
        }
    }


    public static function getComponentsForModule_orginal($moduleId)
    {

        return Component::whereHas('modules', function ($query) use ($moduleId) {
            $query->where('module_id', $moduleId);

        })
            ->where('is_inactive',false)
            ->orderBy('order') // Add ordering by the 'order' column
            ->get(['name', 'wire','icon'])

            ->map(function ($component) {
                return [
                    'name' => $component->name,
                    'wire' => $component->wire ?? session('defaultwire'),
                    'icon' => $component->icon,
                ];
            })
            ->toArray();
    }

    public static function getComponentsForModule($moduleId)
    {
        // List of specific user IDs
        $specificUserIds = [222, 223, 225, 15];

        // Check if the user ID is in the specific list and the module ID is 9
        if (in_array(auth()->id(), $specificUserIds) && $moduleId == 9) {
            // If condition is met, filter components where ID = 41
            return Component::whereHas('modules', function ($query) use ($moduleId) {
                $query->where('module_id', $moduleId);
            })
                ->where('is_inactive', false)
                ->whereIn('id', [39,41])  // Filter for component with ID = 41
                ->orderBy('order')
                ->get(['name', 'wire', 'icon'])
                ->map(function ($component) {
                    return [
                        'name' => $component->name,
                        'wire' => $component->wire ?? session('defaultwire'),
                        'icon' => $component->icon,
                    ];
                })
                ->toArray();
        } else {
            // Default behavior for other cases
            return Component::whereHas('modules', function ($query) use ($moduleId) {
                $query->where('module_id', $moduleId);
            })
                ->where('is_inactive', false)
                ->orderBy('order')
                ->get(['name', 'wire', 'icon'])
                ->map(function ($component) {
                    return [
                        'name' => $component->name,
                        'wire' => $component->wire ?? session('defaultwire'),
                        'icon' => $component->icon,
                    ];
                })
                ->toArray();
        }
    }

}
