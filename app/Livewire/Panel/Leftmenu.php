<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\MenuCoordinator;

class Leftmenu extends Component
{
    public $apps = [];
    public $modules = [];
    public $selectedAppId = null;
    public $selectedModuleId=null;

    public function mount()
    {
        $this->apps = MenuCoordinator::getApps();
        $this->selectedAppId = MenuCoordinator::getSelectedAppId() ?? $this->apps[0]['id'] ?? null;
        $firstModuleId = MenuCoordinator::selectApp($this->selectedAppId);
        $this->modules = MenuCoordinator::getAppModules($this->selectedAppId);

        if ($firstModuleId) {
            $this->dispatch('moduleSelected', $firstModuleId);
        }
    }

    public function selectApp($appId)
    {
        $this->selectedAppId = $appId;
        $this->modules = MenuCoordinator::getAppModules($appId);

        if (!empty($this->modules)) {
            $firstModuleId = $this->modules[0]['id'];
            $this->dispatch('moduleSelected', $firstModuleId); // Load Topmenu
        } else {
            MenuCoordinator::resetAll();
            MenuCoordinator::selectWire(session('defaultwire'));
            $this->dispatch('moduleSelected', null); // ❗ Clear topmenu
            $this->dispatch('wireSelected', session('defaultwire'));
        }
    }

    public function selectWire($wire)
    {
        MenuCoordinator::selectWire($wire);
        $this->selectedWire = $wire;
        $this->dispatch('wireSelected', $wire);
    }

//    public function selectApp($appId)
//    {
//        $this->selectedAppId = $appId;
//        $firstModuleId = MenuCoordinator::selectApp($appId);
//        $this->modules = MenuCoordinator::getAppModules($appId);
//
//        if ($firstModuleId) {
//            $this->dispatch('moduleSelected', $firstModuleId);
//        }
//    }

    public function selectModule($moduleId)
    {
        if (!$moduleId) {
            MenuCoordinator::resetAll();
            $this->dispatch('wireSelected', session('defaultwire'));
            return;
        }

        // Save and update selections via MenuCoordinator
        session(['selectedModuleId' => $moduleId]);
        $wire = MenuCoordinator::selectModule($moduleId);

        // Dispatch to Topmenu and MainContent components
        $this->dispatch('moduleSelected', $moduleId); // Refresh Topmenu
        $this->dispatch('wireSelected', $wire);       // Refresh MainContent
    }



    public function render()
    {
        return view('livewire.panel.leftmenu');
    }
}
