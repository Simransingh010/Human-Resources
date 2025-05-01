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

    public static function getModulesForApp($appId)
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

    public static function getComponentsForModule($moduleId)
    {
        return Component::whereHas('modules', function ($query) use ($moduleId) {
            $query->where('module_id', $moduleId);
        })
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
}
