<?php

namespace App\Livewire\Panelr;

use Livewire\Component;
use App\Services\MenuCoordinator;

class Topmenur extends Component
{
    public $moduleWires = [];
    public $selectedWire = '';
    public $selectedModuleId = null;

    public function mount()
    {
        // Determine selected module from current route
        $currentRoute = request()->route()->getName();
        $modules = MenuCoordinator::getAppModules(MenuCoordinator::getSelectedAppId());
        
        foreach ($modules as $module) {
            foreach ($module['wires'] ?? [] as $wire) {
                if (isset($wire['route']) && $wire['route'] === $currentRoute) {
                    $this->selectedModuleId = $module['id'];
                    $this->moduleWires = $module['wires'];
                    $this->selectedWire = $wire['wire'] ?? '';
                    break 2;
                }
            }
        }
    }

    public function selectWire($route)
    {
        return redirect()->route($route);
    }

    public function render()
    {
        return view('livewire.panelr.topmenur');
    }
}
