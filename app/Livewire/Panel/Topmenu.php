<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\MenuCoordinator;

class Topmenu extends Component
{
    public $moduleWires = [];
    public $selectedWire = '';
    public $currentModuleId = null;

    public function mount()
    {
        $moduleId = MenuCoordinator::getSelectedModuleId();
        if ($moduleId) {
            $this->currentModuleId = $moduleId;
            $this->moduleWires = MenuCoordinator::getModuleWires($moduleId);
            $this->selectedWire = MenuCoordinator::getSelectedWire() ?? '';
        }
    }

    #[On('moduleSelected')]
    public function loadComponents($moduleId)
    {
        if (!$moduleId) {
            return;
        }
        
        $this->currentModuleId = $moduleId;
        $this->moduleWires = MenuCoordinator::getModuleWires($moduleId);
        $wire = MenuCoordinator::selectModule($moduleId);
        $this->selectedWire = $wire;
        $this->dispatch('wireSelected', $wire);
    }

    public function selectWire($wire)
    {
        \Log::info('Topmenu selectWire called', [
            'wire' => $wire,
            'currentModuleId' => $this->currentModuleId,
            'isRouteBased' => MenuCoordinator::isRouteBased($wire),
        ]);
        
        MenuCoordinator::selectWire($wire);
        $this->selectedWire = $wire;
        
        // Check if this wire has a dedicated route
        if (MenuCoordinator::isRouteBased($wire)) {
            $url = MenuCoordinator::getRouteUrl($wire, $this->currentModuleId, session('selectedAppId'));
            \Log::info('Topmenu redirecting to route', ['url' => $url]);
            if ($url) {
                return $this->redirect($url, navigate: true);
            }
        }
        
        // Otherwise dispatch wireSelected for wire-based navigation
        \Log::info('Topmenu dispatching wireSelected', ['wire' => $wire]);
        $this->dispatch('wireSelected', $wire);
    }

    public function render()
    {
        return view('livewire.panel.topmenu');
    }
}
